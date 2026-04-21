<?php

namespace App\Domain\Workspaces\Application\UseCases;

use App\Domain\Enterprises\Exceptions\InvitationExpiredException;
use App\Domain\Enterprises\Exceptions\InvitationInvalidException;
use App\Domain\Enterprises\Exceptions\InvitationMemberExistsException;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class AcceptWorkspaceInvitationUseCase
{
    public function execute(
        Invitation $invitation,
        string     $password,
        ?string    $name,
    ): array {
        if (!$invitation->isPending()) {
            if ($invitation->expires_at->isPast()) {
                throw new InvitationExpiredException();
            }
            throw new InvitationInvalidException();
        }

        $workspace = Workspace::find($invitation->invitable_id);

        if (!$workspace) {
            throw new InvitationInvalidException();
        }

        return DB::transaction(function () use ($invitation, $password, $name, $workspace) {
            $user = User::where('email', $invitation->email)->first();

            if ($user) {
                if (!Hash::check($password, $user->password)) {
                    throw new \App\Domain\Auth\Exceptions\InvalidCredentialsException();
                }
            } else {
                if (!$name) {
                    throw new InvitationInvalidException();
                }
                $user = User::create([
                    'name'     => $name,
                    'email'    => $invitation->email,
                    'password' => Hash::make($password),
                ]);
            }

            // Ensure user is enterprise member
            $alreadyEnterpriseMember = EnterpriseMember::where('enterprise_id', $workspace->enterprise_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();

            if (!$alreadyEnterpriseMember) {
                $memberRole = EnterpriseRole::whereNull('enterprise_id')
                    ->where('name', 'member')
                    ->first();

                if (!$memberRole) {
                    throw new RuntimeException('Global enterprise member role not found');
                }

                EnterpriseMember::create([
                    'enterprise_id' => $workspace->enterprise_id,
                    'user_id'       => $user->id,
                    'role_id'       => $memberRole->id,
                    'status'        => 'active',
                ]);
            }

            // Add to workspace if not already a member
            $alreadyMember = WorkspaceMember::where('workspace_id', $workspace->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyMember) {
                throw new InvitationMemberExistsException();
            }

            WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $user->id,
                'role_id'      => $invitation->workspace_role_id,
            ]);

            $invitation->update([
                'status'      => 'accepted',
                'accepted_at' => now(),
            ]);

            $accessToken  = JWTAuth::fromUser($user);
            $refreshToken = $user->refreshTokens()->create([
                'token'      => \Illuminate\Support\Str::random(64),
                'expires_at' => now()->addDays(30),
            ]);

            return [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken->token,
                'workspace_id'  => $workspace->getHashId(),
            ];
        });
    }
}
