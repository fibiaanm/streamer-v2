<?php

namespace App\Domain\Workspaces\Application\UseCases;

use App\Jobs\SendInvitationEmailJob;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceRole;
use Illuminate\Support\Str;

class SendWorkspaceInvitationUseCase
{
    public function execute(
        Workspace     $workspace,
        User          $invitedBy,
        string        $email,
        WorkspaceRole $role,
    ): Invitation {
        $invitation = $this->createOrRefresh($workspace, $invitedBy, $email, $role);
        SendInvitationEmailJob::dispatch($invitation->id);
        return $invitation;
    }

    private function createOrRefresh(
        Workspace     $workspace,
        User          $invitedBy,
        string        $email,
        WorkspaceRole $role,
    ): Invitation {
        $existing = Invitation::where('invitable_type', Workspace::class)
            ->where('invitable_id', $workspace->id)
            ->where('email', $email)
            ->whereIn('status', ['pending', 'expired'])
            ->first();

        if ($existing) {
            $existing->update([
                'workspace_role_id' => $role->id,
                'token'             => Str::uuid()->toString(),
                'status'            => 'pending',
                'expires_at'        => now()->addDays(7),
            ]);

            return $existing->fresh();
        }

        return Invitation::create([
            'invitable_type'    => Workspace::class,
            'invitable_id'      => $workspace->id,
            'invited_by_user_id' => $invitedBy->id,
            'email'             => $email,
            'workspace_role_id' => $role->id,
            'token'             => Str::uuid()->toString(),
            'status'            => 'pending',
            'expires_at'        => now()->addDays(7),
        ]);
    }
}
