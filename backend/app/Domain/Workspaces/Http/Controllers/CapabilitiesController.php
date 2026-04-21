<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CapabilitiesController
{
    public function __invoke(Request $request, string $workspaceId): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $member = WorkspaceMember::where('workspace_id', $workspace->id)
                ->where('user_id', $request->user()->id)
                ->with('role.permissions')
                ->first();

            if (!$member) {
                throw new WorkspaceForbiddenException();
            }

            $permissions = $member->role->permissions->pluck('name')->values()->all();

            return ResponseFormatter::success($permissions);

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.capabilities_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
