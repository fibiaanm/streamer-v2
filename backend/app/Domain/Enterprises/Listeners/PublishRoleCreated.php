<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\RoleCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishRoleCreated
{
    public function handle(RoleCreated $event): void
    {
        try {
            $enterprise = $event->enterprise;
            $role       = $event->role;
            $role->loadMissing('permissions');

            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'role.created',
                'data'  => [
                    'id'          => $role->getHashId(),
                    'name'        => $role->name,
                    'is_global'   => $role->isGlobal(),
                    'permissions' => $role->permissions->pluck('name')->values()->all(),
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('enterprises.publish_role_created_failed', ['exception' => $e]);
        }
    }
}
