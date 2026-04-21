<?php

namespace App\Domain\Workspaces\Listeners;

use App\Domain\Workspaces\Events\WorkspaceMemberRemoved;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishWorkspaceMemberRemoved
{
    public function handle(WorkspaceMemberRemoved $event): void
    {
        try {
            $workspace = $event->workspace;
            $user      = User::find($event->userId);

            Redis::connection('pubsub')->publish("workspace.{$workspace->getHashId()}", json_encode([
                'event' => 'member.removed',
                'data'  => [
                    'userId' => $user?->getHashId(),
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('workspaces.publish_member_removed_failed', ['exception' => $e]);
        }
    }
}
