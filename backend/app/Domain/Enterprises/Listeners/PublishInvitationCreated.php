<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\InvitationCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishInvitationCreated
{
    public function handle(InvitationCreated $event): void
    {
        try {
            $enterprise = $event->enterprise;
            $invitation = $event->invitation;
            $invitation->loadMissing(['enterpriseRole', 'invitedBy']);

            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'invitation.created',
                'data'  => [
                    'id'         => $invitation->getHashId(),
                    'email'      => $invitation->email,
                    'status'     => $invitation->status,
                    'expires_at' => $invitation->expires_at->toISOString(),
                    'role'       => [
                        'id'   => $invitation->enterpriseRole->getHashId(),
                        'name' => $invitation->enterpriseRole->name,
                    ],
                    'invited_by' => [
                        'id'   => $invitation->invitedBy->getHashId(),
                        'name' => $invitation->invitedBy->name,
                    ],
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('enterprises.publish_invitation_created_failed', ['exception' => $e]);
        }
    }
}
