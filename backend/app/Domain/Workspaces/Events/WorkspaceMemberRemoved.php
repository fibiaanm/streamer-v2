<?php

namespace App\Domain\Workspaces\Events;

use App\Models\Workspace;

class WorkspaceMemberRemoved
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly int       $userId,
    ) {}
}
