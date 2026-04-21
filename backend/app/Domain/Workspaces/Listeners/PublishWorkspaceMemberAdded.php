<?php

namespace App\Domain\Workspaces\Listeners;

use App\Domain\Workspaces\Events\WorkspaceMemberAdded;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishWorkspaceMemberAdded
{
    public function handle(WorkspaceMemberAdded $event): void
    {
        try {
            $workspace = $event->workspace;
            $member    = $event->member;
            $member->loadMissing(['user', 'role']);

            Redis::connection('pubsub')->publish("workspace.{$workspace->getHashId()}", json_encode([
                'event' => 'member.added',
                'data'  => [
                    'memberId' => $member->getHashId(),
                    'user'     => ['id' => $member->user->getHashId(), 'name' => $member->user->name],
                    'role'     => ['id' => $member->role->getHashId(), 'name' => $member->role->name],
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('workspaces.publish_member_added_failed', ['exception' => $e]);
        }
    }
}
