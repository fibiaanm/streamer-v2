<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\ReminderRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminJobDetailController
{
    public function __invoke(int $id): JsonResponse
    {
        $job = DB::table('jobs')->where('id', $id)->first();

        abort_if(! $job, 404);

        $run = ReminderRun::where('job_id', (string) $id)
            ->with(['reminders.event.user'])
            ->first();

        return response()->json([
            'data' => [
                'id'           => $job->id,
                'queue'        => $job->queue,
                'display_name' => data_get(json_decode($job->payload, true), 'displayName', 'Unknown'),
                'attempts'     => $job->attempts,
                'available_at' => $job->available_at,
                'created_at'   => $job->created_at,
                'run'          => $run ? [
                    'id'         => $run->id,
                    'kind'       => $run->kind,
                    'run_at'     => $run->run_at,
                    'status'     => $run->status,
                    'reminders'  => $run->reminders->map(fn ($r) => [
                        'id'       => $r->id,
                        'kind'     => $r->kind,
                        'fire_at'  => $r->fire_at,
                        'status'   => $r->status,
                        'fired_at' => $r->fired_at,
                        'event'    => $r->event ? [
                            'id'       => $r->event->id,
                            'content'  => $r->event->content,
                            'event_at' => $r->event->event_at,
                            'type'     => $r->event->type,
                            'user'     => $r->event->user ? [
                                'name'  => $r->event->user->name,
                                'email' => $r->event->user->email,
                            ] : null,
                        ] : null,
                    ]),
                ] : null,
            ],
        ]);
    }
}
