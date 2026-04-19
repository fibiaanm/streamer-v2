<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Exceptions\InvitationExpiredException;
use App\Domain\Enterprises\Exceptions\InvitationInvalidException;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Enterprise;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShowInvitationController
{
    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $invitation = Invitation::where('token', $token)
                ->where('invitable_type', Enterprise::class)
                ->with('invitable')
                ->first();

            if (!$invitation) {
                throw new InvitationInvalidException();
            }

            if (!$invitation->isPending()) {
                if ($invitation->expires_at->isPast()) {
                    throw new InvitationExpiredException();
                }
                throw new InvitationInvalidException();
            }

            return ResponseFormatter::success([
                'email'           => $invitation->email,
                'enterprise_name' => $invitation->invitable->name,
                'role_name'       => $invitation->enterpriseRole->name,
                'user_exists'     => User::where('email', $invitation->email)->exists(),
            ]);

        } catch (InvitationInvalidException | InvitationExpiredException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('enterprises.show_invitation_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
