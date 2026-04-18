<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\RoleUpdated;
use App\Models\EnterpriseMember;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishRoleUpdated
{
    public function handle(RoleUpdated $event): void
    {
        try {
            $enterprise = $event->enterprise;
            $role       = $event->role;
            $role->loadMissing('permissions');

            $data = [
                'id'          => $role->getHashId(),
                'name'        => $role->name,
                'is_global'   => $role->isGlobal(),
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ];

            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'role.updated',
                'data'  => $data,
            ]));

            EnterpriseMember::where('enterprise_id', $enterprise->id)
                ->where('role_id', $role->id)
                ->where('status', 'active')
                ->pluck('user_id')
                ->each(function (int $userId) use ($data) {
                    Redis::connection('pubsub')->publish("user.{$userId}", json_encode([
                        'event' => 'role.permissions_changed',
                        'data'  => $data,
                    ]));
                });
        } catch (Throwable $e) {
            Log::error('enterprises.publish_role_updated_failed', ['exception' => $e]);
        }
    }
}
