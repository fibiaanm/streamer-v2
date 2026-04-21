<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Events\WorkspaceMemberRemoved;
use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveMemberController
{
    public function __construct(private readonly WorkspacePermissionGate $permissionGate) {}

    public function __invoke(Request $request, string $workspaceId, string $memberId): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $this->permissionGate->authorize($request->user(), $workspace, 'workspace.members.delete');

            $id     = HashId::decode($memberId);
            $member = $id ? WorkspaceMember::find($id) : null;

            if (!$member || $member->workspace_id !== $workspace->id) {
                throw new WorkspaceNotFoundException();
            }

            $userId = $member->user_id;
            $member->delete();

            event(new WorkspaceMemberRemoved($workspace, $userId));

            return ResponseFormatter::noContent();

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.remove_member_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
