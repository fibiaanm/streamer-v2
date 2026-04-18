<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\MemberRoleChanged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishMemberRoleChanged
{
    public function handle(MemberRoleChanged $event): void
    {
        try {
            $enterprise = $event->enterprise;
            $member     = $event->member;
            $role       = $event->role;
            $role->loadMissing('permissions');

            // List update for all enterprise admins watching MembersPanel
            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'member.role_changed',
                'data'  => [
                    'memberId' => $member->getHashId(),
                    'role'     => ['id' => $role->getHashId(), 'name' => $role->name],
                ],
            ]));

            // Session update for the affected member
            Redis::connection('pubsub')->publish("user.{$member->user_id}", json_encode([
                'event' => 'member.role_assigned',
                'data'  => [
                    'role' => [
                        'id'          => $role->getHashId(),
                        'name'        => $role->name,
                        'permissions' => $role->permissions->pluck('name')->values()->all(),
                    ],
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('enterprises.publish_member_role_changed_failed', ['exception' => $e]);
        }
    }
}
