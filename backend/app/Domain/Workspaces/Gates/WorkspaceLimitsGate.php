<?php

namespace App\Domain\Workspaces\Gates;

use App\Domain\Workspaces\Exceptions\WorkspaceDepthExceededException;
use App\Exceptions\PlanLimitExceededException;
use App\Models\User;
use App\Models\Workspace;
use App\Values\ResolvedLimits;
use Illuminate\Support\Facades\DB;

class WorkspaceLimitsGate
{
    public function checkCreate(User $user, ResolvedLimits $limits, ?Workspace $parent = null): void
    {
        if ($parent === null) {
            $this->checkRootLimit($user, $limits);
        } else {
            $this->checkDepthLimit($parent, $limits);
        }
    }

    private function checkRootLimit(User $user, ResolvedLimits $limits): void
    {
        $max = $limits->maxWorkspaces();

        if ($max === -1) {
            return;
        }

        $count = Workspace::where('owner_user_id', $user->id)
            ->whereNull('parent_workspace_id')
            ->count();

        if ($count >= $max) {
            throw new PlanLimitExceededException('workspaces');
        }
    }

    private function checkDepthLimit(Workspace $parent, ResolvedLimits $limits): void
    {
        $max = $limits->maxDepth();

        if ($max === -1) {
            return;
        }

        $depth = DB::selectOne(
            'SELECT nlevel(path) as depth FROM workspaces WHERE id = ?',
            [$parent->id],
        )->depth;

        if ($depth >= $max) {
            throw new WorkspaceDepthExceededException();
        }
    }
}
