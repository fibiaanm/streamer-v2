<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TokenUsageSeeder extends Seeder
{
    private const MODELS = [
        ['model' => 'claude-3-5-sonnet-20241022', 'provider' => 'anthropic', 'weight' => 40],
        ['model' => 'claude-3-haiku-20240307',    'provider' => 'anthropic', 'weight' => 20],
        ['model' => 'gpt-4o',                     'provider' => 'openai',    'weight' => 25],
        ['model' => 'gpt-4o-mini',                'provider' => 'openai',    'weight' => 10],
        ['model' => 'gemini-1.5-pro',             'provider' => 'gemini',    'weight' => 5],
    ];

    // type => [weight, input_range, output_range]
    private const TYPES = [
        'text'      => ['weight' => 65, 'input' => [300, 4000], 'output' => [100, 1500]],
        'memory'    => ['weight' => 25, 'input' => [500, 3000], 'output' => [200, 1000]],
        'embedding' => ['weight' => 10, 'input' => [100, 500],  'output' => [0, 0]],
    ];

    // email => requests per active day (min, max)
    private const USER_ACTIVITY = [
        'assistant-free@test.com'    => [1, 6],
        'assistant-pro@test.com'     => [8, 20],
        'assistant-premium@test.com' => [18, 45],
    ];

    public function run(): void
    {
        if (DB::table('token_usage_daily')->count() > 0) {
            $this->command->info('TokenUsageSeeder: already has data, skipping.');
            return;
        }

        $users = User::whereIn('email', array_keys(self::USER_ACTIVITY))
            ->pluck('id', 'email');

        if ($users->isEmpty()) {
            $this->command->warn('TokenUsageSeeder: no assistant users found — run AssistantUserSeeder first.');
            return;
        }

        // daily rollup accumulators: "date|model|provider|type" -> [input, output, count]
        $daily     = [];
        // user daily: "date|user_id" -> [input, output, count]
        $userDaily = [];

        $modelPool = $this->buildWeightedPool(self::MODELS, 'weight');
        $typePool  = $this->buildWeightedPool(array_keys(self::TYPES), null, array_column(self::TYPES, 'weight'));

        for ($daysAgo = 90; $daysAgo >= 0; $daysAgo--) {
            $date    = now()->subDays($daysAgo)->toDateString();
            $weekday = (int) now()->subDays($daysAgo)->format('N'); // 1=Mon … 7=Sun
            $factor  = $weekday >= 6 ? 0.4 : 1.0;                  // weekend slowdown

            foreach ($users as $email => $userId) {
                [$min, $max] = self::USER_ACTIVITY[$email];
                $requests    = (int) round(rand($min, $max) * $factor);

                for ($r = 0; $r < $requests; $r++) {
                    $m    = $modelPool[array_rand($modelPool)];
                    $type = $typePool[array_rand($typePool)];
                    $cfg  = self::TYPES[$type];

                    $input  = rand(...$cfg['input']);
                    $output = rand(...$cfg['output']);

                    $dKey = "{$date}|{$m['model']}|{$m['provider']}|{$type}";
                    $uKey = "{$date}|{$userId}";

                    $daily[$dKey]     ??= [0, 0, 0];
                    $daily[$dKey][0]  += $input;
                    $daily[$dKey][1]  += $output;
                    $daily[$dKey][2]  += 1;

                    $userDaily[$uKey]    ??= [0, 0, 0];
                    $userDaily[$uKey][0] += $input;
                    $userDaily[$uKey][1] += $output;
                    $userDaily[$uKey][2] += 1;
                }
            }
        }

        $dailyRows = [];
        foreach ($daily as $key => [$input, $output, $count]) {
            [$date, $model, $provider, $type] = explode('|', $key);
            $dailyRows[] = [
                'date'          => $date,
                'model'         => $model,
                'provider'      => $provider,
                'type'          => $type,
                'input_tokens'  => $input,
                'output_tokens' => $output,
                'record_count'  => $count,
            ];
        }

        $userDailyRows = [];
        foreach ($userDaily as $key => [$input, $output, $count]) {
            [$date, $userId] = explode('|', $key);
            $userDailyRows[] = [
                'date'          => $date,
                'user_id'       => (int) $userId,
                'input_tokens'  => $input,
                'output_tokens' => $output,
                'record_count'  => $count,
            ];
        }

        foreach (array_chunk($dailyRows, 500) as $chunk) {
            DB::table('token_usage_daily')->insert($chunk);
        }

        foreach (array_chunk($userDailyRows, 500) as $chunk) {
            DB::table('token_usage_user_daily')->insert($chunk);
        }

        DB::table('token_usage_rollup_state')->update([
            'last_verified_id' => 0,
            'last_run_at'      => now(),
        ]);

        $this->command->info(sprintf(
            'TokenUsageSeeder: inserted %d daily rows and %d user-daily rows.',
            count($dailyRows),
            count($userDailyRows),
        ));
    }

    private function buildWeightedPool(array $items, ?string $weightKey, array $weights = []): array
    {
        $pool = [];
        foreach ($items as $i => $item) {
            $w = $weightKey ? $item[$weightKey] : $weights[$i];
            for ($j = 0; $j < $w; $j++) {
                $pool[] = $item;
            }
        }
        return $pool;
    }
}
