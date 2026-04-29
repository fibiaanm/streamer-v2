<?php

namespace App\Domain\Workspaces\Listeners;

use App\Domain\Workspaces\Events\WorkspaceMemberRoleChanged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishWorkspaceMemberRoleChanged
{
    public function handle(WorkspaceMemberRoleChanged $event): void
    {
        try {
            $workspace = $event->workspace;
            $member    = $event->member;
            $role      = $event->role;
            $member->loadMissing('user');

            Redis::connection('pubsub')->publish("workspace.{$workspace->getHashId()}", json_encode([
                'event' => 'member.role_changed',
                'data'  => [
                    'memberId' => $member->getHashId(),
                    'userId'   => $member->user->getHashId(),
                    'role'     => ['id' => $role->getHashId(), 'name' => $role->name],
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('workspaces.publish_member_role_changed_failed', ['exception' => $e]);
        }
    }
}
