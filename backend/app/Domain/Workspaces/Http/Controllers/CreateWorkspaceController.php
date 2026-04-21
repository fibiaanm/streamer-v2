<?php

namespace App\Domain\Workspaces\Http\Controllers;

use App\Domain\Workspaces\Application\UseCases\CreateWorkspaceUseCase;
use App\Domain\Workspaces\Exceptions\WorkspaceDepthExceededException;
use App\Domain\Workspaces\Exceptions\WorkspaceForbiddenException;
use App\Domain\Workspaces\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspaces\Http\Resources\WorkspaceResource;
use App\Exceptions\PlanLimitExceededException;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Workspace;
use App\Services\LimitsResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateWorkspaceController
{
    public function __construct(private readonly CreateWorkspaceUseCase $useCase) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'name'                => ['required', 'string', 'max:128'],
            'parent_workspace_id' => ['nullable', 'string'],
        ]);

        try {
            $enterprise        = $request->attributes->get('active_enterprise');
            $enterpriseProducts = $request->attributes->get('active_enterprise_products');
            $limits            = app(LimitsResolver::class)->resolve($enterpriseProducts);

            $parent = null;
            if ($request->filled('parent_workspace_id')) {
                $parent = Workspace::findByHashId($request->input('parent_workspace_id'));
                if (!$parent) {
                    throw new WorkspaceNotFoundException();
                }
            }

            $workspace = $this->useCase->execute(
                user:       auth()->user(),
                enterprise: $enterprise,
                limits:     $limits,
                name:       $request->input('name'),
                parent:     $parent,
            );

            $workspace->load('owner');

            return ResponseFormatter::created(new WorkspaceResource($workspace));

        } catch (WorkspaceNotFoundException | WorkspaceForbiddenException | WorkspaceDepthExceededException | PlanLimitExceededException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('workspaces.create_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
