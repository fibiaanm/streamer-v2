<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Enterprises\Exceptions\InvitationExpiredException;
use App\Domain\Enterprises\Exceptions\InvitationInvalidException;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShowWorkspaceInvitationController
{
    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $invitation = Invitation::where('token', $token)
                ->where('invitable_type', Workspace::class)
                ->with(['invitable', 'workspaceRole'])
                ->first();

            if (!$invitation) {
                throw new InvitationInvalidException();
            }

            if ($invitation->status !== 'pending') {
                throw new InvitationInvalidException();
            }

            if ($invitation->expires_at->isPast()) {
                throw new InvitationExpiredException();
            }

            return ResponseFormatter::success([
                'email'          => $invitation->email,
                'workspace_name' => $invitation->invitable->name,
                'role_name'      => $invitation->workspaceRole->name,
                'user_exists'    => User::where('email', $invitation->email)->exists(),
            ]);

        } catch (InvitationInvalidException | InvitationExpiredException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.show_invitation_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
