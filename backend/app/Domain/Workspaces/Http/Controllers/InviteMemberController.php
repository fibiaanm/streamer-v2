<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Application\UseCases\SendWorkspaceInvitationUseCase;
use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Exceptions\WorkspaceRoleBaseImmutableException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Domain\Workspaces\Http\Resources\WorkspaceInvitationResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Invitation;
use App\Models\Workspace;
use App\Models\WorkspaceRole;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class InviteMemberController
{
    public function __construct(
        private readonly WorkspacePermissionGate      $permissionGate,
        private readonly SendWorkspaceInvitationUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $workspaceId): JsonResponse
    {
        $request->validate([
            'email'   => ['required', 'email'],
            'role_id' => ['required', 'string'],
        ]);

        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $this->permissionGate->authorize($request->user(), $workspace, 'workspace.members.add');

            $roleId = HashId::decode($request->input('role_id'));
            $role   = $roleId ? WorkspaceRole::find($roleId) : null;

            if (!$role || $role->workspace_id !== $workspace->id) {
                throw new WorkspaceNotFoundException();
            }

            if ($role->is_base && $role->name === 'owner') {
                throw new WorkspaceRoleBaseImmutableException();
            }

            $invitation = $this->useCase->execute(
                workspace:  $workspace,
                invitedBy:  $request->user(),
                email:      $request->input('email'),
                role:       $role,
            );

            $invitation = Invitation::find($invitation->id)->load(['workspaceRole', 'invitedBy']);

            return ResponseFormatter::created(new WorkspaceInvitationResource($invitation));

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException | WorkspaceRoleBaseImmutableException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.invite_member_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
