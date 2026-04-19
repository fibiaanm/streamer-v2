<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\MemberAdded;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishMemberAdded
{
    public function handle(MemberAdded $event): void
    {
        try {
            $enterprise = $event->enterprise;
            $member     = $event->member;
            $invitation = $event->invitation;

            $member->loadMissing(['user', 'role']);

            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'member.added',
                'data'  => [
                    'member' => [
                        'id'     => $member->getHashId(),
                        'status' => $member->status,
                        'user'   => [
                            'id'    => $member->user->getHashId(),
                            'name'  => $member->user->name,
                            'email' => $member->user->email,
                        ],
                        'role'   => [
                            'id'   => $member->role->getHashId(),
                            'name' => $member->role->name,
                        ],
                    ],
                    'invitation_id' => $invitation->getHashId(),
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('enterprises.publish_member_added_failed', ['exception' => $e]);
        }
    }
}
