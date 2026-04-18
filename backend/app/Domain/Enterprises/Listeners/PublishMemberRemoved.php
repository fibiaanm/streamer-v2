<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\MemberRemoved;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishMemberRemoved
{
    public function handle(MemberRemoved $event): void
    {
        try {
            $enterprise = $event->enterprise;
            $member     = $event->member;

            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'member.removed',
                'data'  => ['id' => $member->getHashId()],
            ]));

            Redis::connection('pubsub')->publish("user.{$member->user_id}", json_encode([
                'event' => 'member.kicked',
                'data'  => [
                    'enterpriseId'   => $enterprise->getHashId(),
                    'enterpriseName' => $enterprise->name,
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('enterprises.publish_member_removed_failed', ['exception' => $e]);
        }
    }
}
