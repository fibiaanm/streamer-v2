<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\RoleDeleted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishRoleDeleted
{
    public function handle(RoleDeleted $event): void
    {
        try {
            $enterprise = $event->enterprise;
            $role       = $event->role;

            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'role.deleted',
                'data'  => ['id' => $role->getHashId()],
            ]));
        } catch (Throwable $e) {
            Log::error('enterprises.publish_role_deleted_failed', ['exception' => $e]);
        }
    }
}
