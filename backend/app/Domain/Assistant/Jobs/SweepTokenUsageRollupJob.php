<?php

namespace App\Domain\Assistant\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SweepTokenUsageRollupJob
{
    public function handle(): void
    {
        try {
            Log::info('SweepTokenUsageRollup: starting');

            $state  = DB::table('token_usage_rollup_state')->first();
            $lastId = (int) ($state->last_verified_id ?? 0);

            Log::info('SweepTokenUsageRollup: watermark read', ['last_verified_id' => $lastId, 'last_run_at' => $state->last_run_at ?? null]);

            $maxId = (int) DB::table('token_usages')->where('id', '>', $lastId)->max('id');

            if ($maxId === 0) {
                DB::table('token_usage_rollup_state')->update(['last_run_at' => now()]);
                Log::info('SweepTokenUsageRollup: nothing to process');
                return;
            }

            Log::info('SweepTokenUsageRollup: aggregating delta', ['from_id' => $lastId + 1, 'to_id' => $maxId]);

            $daily = DB::table('token_usages')
                ->whereBetween('id', [$lastId + 1, $maxId])
                ->selectRaw("DATE(created_at) AS date, model, provider, type, COALESCE(SUM(input_tokens),0) AS input_tokens, COALESCE(SUM(output_tokens),0) AS output_tokens, COUNT(*) AS record_count")
                ->groupByRaw('DATE(created_at), model, provider, type')
                ->get();

            $userDaily = DB::table('token_usages')
                ->whereBetween('id', [$lastId + 1, $maxId])
                ->selectRaw("DATE(created_at) AS date, user_id, COALESCE(SUM(input_tokens),0) AS input_tokens, COALESCE(SUM(output_tokens),0) AS output_tokens, COUNT(*) AS record_count")
                ->groupByRaw('DATE(created_at), user_id')
                ->get();

            Log::info('SweepTokenUsageRollup: upserting', ['daily_rows' => $daily->count(), 'user_rows' => $userDaily->count()]);

            DB::transaction(function () use ($daily, $userDaily, $maxId) {
                $this->upsertDaily($daily);
                $this->upsertUserDaily($userDaily);

                DB::table('token_usage_rollup_state')->update([
                    'last_verified_id' => $maxId,
                    'last_run_at'      => now(),
                ]);
            });

            Log::info('SweepTokenUsageRollup: done', ['new_watermark' => $maxId]);
        } catch (Throwable $e) {
            Log::error('SweepTokenUsageRollup: failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function upsertDaily(Collection $rows): void
    {
        if ($rows->isEmpty()) return;

        $placeholders = implode(', ', array_fill(0, $rows->count(), '(?, ?, ?, ?, ?, ?, ?)'));
        $bindings = [];

        foreach ($rows as $row) {
            array_push($bindings,
                $row->date, $row->model, $row->provider, $row->type,
                $row->input_tokens, $row->output_tokens, $row->record_count,
            );
        }

        DB::statement("
            INSERT INTO token_usage_daily (date, model, provider, type, input_tokens, output_tokens, record_count)
            VALUES {$placeholders}
            ON CONFLICT (date, model, provider, type) DO UPDATE SET
                input_tokens  = token_usage_daily.input_tokens  + excluded.input_tokens,
                output_tokens = token_usage_daily.output_tokens + excluded.output_tokens,
                record_count  = token_usage_daily.record_count  + excluded.record_count
        ", $bindings);
    }

    private function upsertUserDaily(Collection $rows): void
    {
        if ($rows->isEmpty()) return;

        $placeholders = implode(', ', array_fill(0, $rows->count(), '(?, ?, ?, ?, ?)'));
        $bindings = [];

        foreach ($rows as $row) {
            array_push($bindings,
                $row->date, $row->user_id,
                $row->input_tokens, $row->output_tokens, $row->record_count,
            );
        }

        DB::statement("
            INSERT INTO token_usage_user_daily (date, user_id, input_tokens, output_tokens, record_count)
            VALUES {$placeholders}
            ON CONFLICT (date, user_id) DO UPDATE SET
                input_tokens  = token_usage_user_daily.input_tokens  + excluded.input_tokens,
                output_tokens = token_usage_user_daily.output_tokens + excluded.output_tokens,
                record_count  = token_usage_user_daily.record_count  + excluded.record_count
        ", $bindings);
    }
}
