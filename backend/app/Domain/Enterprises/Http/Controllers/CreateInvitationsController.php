<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Application\UseCases\SendEnterpriseInvitationsUseCase;
use App\Domain\Enterprises\Http\Resources\InvitationResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateInvitationsController
{
    public function __construct(
        private readonly SendEnterpriseInvitationsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'emails'   => ['required', 'array', 'min:1', 'max:20'],
            'emails.*' => ['required', 'email'],
        ]);

        try {
            $enterprise  = $request->attributes->get('active_enterprise');
            $invitedBy   = $request->user();

            $invitations = $this->useCase->execute(
                $enterprise,
                $invitedBy,
                $request->input('emails'),
            );

            $ids = $invitations->pluck('id');
            $invitations = Invitation::whereIn('id', $ids)
                ->with(['enterpriseRole', 'invitedBy'])
                ->get();

            return ResponseFormatter::created(
                InvitationResource::collection($invitations)->resolve(),
            );

        } catch (Throwable $e) {
            Log::error('enterprises.create_invitations_unexpected', ['exception' => $e->getMessage()]);
            return ResponseFormatter::serverError();
        }
    }
}
