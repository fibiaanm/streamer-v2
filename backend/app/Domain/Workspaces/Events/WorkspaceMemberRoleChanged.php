<?php

namespace App\Domain\Workspaces\Events;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;

class WorkspaceMemberRoleChanged
{
    public function __construct(
        public readonly Workspace       $workspace,
        public readonly WorkspaceMember $member,
        public readonly WorkspaceRole   $role,
    ) {}
}
