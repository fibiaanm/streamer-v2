<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\InvitationCancelled;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishInvitationCancelled
{
    public function handle(InvitationCancelled $event): void
    {
        try {
            $enterprise = $event->enterprise;
            $invitation = $event->invitation;

            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'invitation.cancelled',
                'data'  => ['id' => $invitation->getHashId()],
            ]));
        } catch (Throwable $e) {
            Log::error('enterprises.publish_invitation_cancelled_failed', ['exception' => $e]);
        }
    }
}
