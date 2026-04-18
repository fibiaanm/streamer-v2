<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Http\Resources\InvitationResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Enterprise;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListInvitationsController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $invitations = Invitation::where('invitable_type', Enterprise::class)
                ->where('invitable_id', $enterprise->id)
                ->where('status', 'pending')
                ->with(['enterpriseRole', 'invitedBy'])
                ->get();

            return ResponseFormatter::success(InvitationResource::collection($invitations));

        } catch (Throwable $e) {
            Log::error('enterprises.list_invitations_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
