<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\EventReminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminJobDetailController
{
    public function __invoke(int $id): JsonResponse
    {
        $job = DB::table('jobs')->where('id', $id)->first();

        abort_if(! $job, 404);

        $reminder = EventReminder::where('job_id', (string) $id)
            ->with(['event.user'])
            ->first();

        return response()->json([
            'data' => [
                'id'           => $job->id,
                'queue'        => $job->queue,
                'display_name' => data_get(json_decode($job->payload, true), 'displayName', 'Unknown'),
                'attempts'     => $job->attempts,
                'available_at' => $job->available_at,
                'created_at'   => $job->created_at,
                'reminder'     => $reminder ? [
                    'id'       => $reminder->id,
                    'message'  => $reminder->message,
                    'fire_at'  => $reminder->fire_at,
                    'status'   => $reminder->status,
                    'fired_at' => $reminder->fired_at,
                    'event'    => $reminder->event ? [
                        'id'       => $reminder->event->id,
                        'content'  => $reminder->event->content,
                        'event_at' => $reminder->event->event_at,
                        'type'     => $reminder->event->type,
                        'user_id'  => $reminder->event->user_id,
                        'user'     => $reminder->event->user ? [
                            'name'  => $reminder->event->user->name,
                            'email' => $reminder->event->user->email,
                        ] : null,
                    ] : null,
                ] : null,
            ],
        ]);
    }
}
