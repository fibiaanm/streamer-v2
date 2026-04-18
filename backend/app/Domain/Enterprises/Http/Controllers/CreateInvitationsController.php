<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Application\UseCases\SendEnterpriseInvitationsUseCase;
use App\Domain\Enterprises\Events\InvitationCreated;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleAssignNotAllowedException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleBaseImmutableException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleNotFoundException;
use App\Domain\Enterprises\Http\Resources\InvitationResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterpriseRole;
use App\Models\Invitation;
use App\Services\HashId;
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
            'role_id'  => ['sometimes', 'nullable', 'string'],
        ]);

        try {
            $enterprise    = $request->attributes->get('active_enterprise');
            $invitedBy     = $request->user();
            $currentMember = $request->attributes->get('active_enterprise_member');

            $role = null;

            if ($request->filled('role_id')) {
                $roleId = HashId::decode($request->input('role_id'));
                $role   = $roleId ? EnterpriseRole::find($roleId) : null;

                if (!$role || ($role->enterprise_id !== null && $role->enterprise_id !== $enterprise->id)) {
                    throw new EnterpriseRoleNotFoundException();
                }

                if ($role->isOwner()) {
                    throw new EnterpriseRoleBaseImmutableException();
                }

                $role->loadMissing('permissions');
                $currentMember->loadMissing('role.permissions');
                $myPerms   = $currentMember->role->permissions->pluck('name')->all();
                $rolePerms = $role->permissions->pluck('name')->all();

                if (!empty(array_diff($rolePerms, $myPerms))) {
                    throw new EnterpriseRoleAssignNotAllowedException();
                }
            }

            $invitations = $this->useCase->execute(
                $enterprise,
                $invitedBy,
                $request->input('emails'),
                $role,
            );

            $ids = $invitations->pluck('id');
            $invitations = Invitation::whereIn('id', $ids)
                ->with(['enterpriseRole', 'invitedBy'])
                ->get();

            foreach ($invitations as $invitation) {
                event(new InvitationCreated($enterprise, $invitation));
            }

            return ResponseFormatter::created(
                InvitationResource::collection($invitations)->resolve(),
            );

        } catch (EnterpriseRoleNotFoundException | EnterpriseRoleBaseImmutableException | EnterpriseRoleAssignNotAllowedException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('enterprises.create_invitations_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
