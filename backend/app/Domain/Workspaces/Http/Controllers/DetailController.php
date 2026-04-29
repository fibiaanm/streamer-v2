<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Gates\WorkspacePermissionGate;
use App\Domain\Workspaces\Http\Resources\WorkspaceResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DetailController
{
    public function __construct(private readonly WorkspacePermissionGate $permissionGate) {}

    public function __invoke(Request $request, string $workspaceId): JsonResponse
    {
        try {
            $enterprise = $request->attributes->get('active_enterprise');
            $user       = $request->user();

            $workspace = Workspace::findByHashId($workspaceId);
            if (!$workspace || $workspace->enterprise_id !== $enterprise->id) {
                throw new WorkspaceNotFoundException();
            }

            $member = $this->permissionGate->authorize($user, $workspace, 'workspace.view');

            $workspace->load('owner');

            $ancestors = Workspace::whereRaw('path @> ?::ltree', [$workspace->path])
                ->where('id', '!=', $workspace->id)
                ->where('enterprise_id', $enterprise->id)
                ->with('owner')
                ->orderByRaw('nlevel(path) ASC')
                ->get();

            $children = Workspace::where('parent_workspace_id', $workspace->id)
                ->with('owner')
                ->get();

            $capabilities = $member->role->permissions->pluck('name')->values()->all();

            return ResponseFormatter::success([
                'workspace'    => new WorkspaceResource($workspace),
                'ancestors'    => WorkspaceResource::collection($ancestors)->resolve(),
                'children'     => WorkspaceResource::collection($children)->resolve(),
                'mode'         => $workspace->owner_user_id === $user->id ? 'my' : 'shared',
                'role'         => ['id' => $member->role->getHashId(), 'name' => $member->role->name],
                'capabilities' => $capabilities,
            ]);

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.detail_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
