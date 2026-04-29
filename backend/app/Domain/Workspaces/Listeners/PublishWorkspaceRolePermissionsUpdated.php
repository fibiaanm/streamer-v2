<?php

namespace App\Domain\Workspaces\Listeners;

use App\Domain\Workspaces\Events\WorkspaceRolePermissionsUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishWorkspaceRolePermissionsUpdated
{
    public function handle(WorkspaceRolePermissionsUpdated $event): void
    {
        try {
            $workspace = $event->workspace;
            $role      = $event->role;
            $role->loadMissing('permissions');

            Redis::connection('pubsub')->publish(
                "workspace.{$workspace->getHashId()}.role.{$role->getHashId()}",
                json_encode([
                    'event' => 'role.permissions_updated',
                    'data'  => [
                        'roleId'      => $role->getHashId(),
                        'permissions' => $role->permissions->pluck('name')->values()->all(),
                    ],
                ])
            );
        } catch (Throwable $e) {
            Log::error('workspaces.publish_role_permissions_updated_failed', ['exception' => $e]);
        }
    }
}
