<?php

namespace App\Domain\Workspaces\Events;

use App\Models\Workspace;
use App\Models\WorkspaceRole;

class WorkspaceRolePermissionsUpdated
{
    public function __construct(
        public readonly Workspace     $workspace,
        public readonly WorkspaceRole $role,
    ) {}
}
