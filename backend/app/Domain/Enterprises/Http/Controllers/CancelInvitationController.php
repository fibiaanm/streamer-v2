<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Events\InvitationCancelled;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Enterprise;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CancelInvitationController
{
    public function __invoke(Request $request, string $invitationId): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $invitation = Invitation::findByHashId($invitationId);

            if (
                ! $invitation
                || $invitation->invitable_type !== Enterprise::class
                || $invitation->invitable_id !== $enterprise->id
                || $invitation->status !== 'pending'
            ) {
                return response()->json([
                    'error' => ['code' => 'enterprise.invitation_invalid', 'context' => []],
                ], 404);
            }

            $invitation->update(['status' => 'revoked']);

            event(new InvitationCancelled($enterprise, $invitation));

            return ResponseFormatter::noContent();

        } catch (Throwable $e) {
            Log::error('enterprises.cancel_invitation_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
