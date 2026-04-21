<?php

namespace App\Domain\Workspaces\Events;

use App\Models\Workspace;
use App\Models\WorkspaceMember;

class WorkspaceMemberAdded
{
    public function __construct(
        public readonly Workspace       $workspace,
        public readonly WorkspaceMember $member,
    ) {}
}
