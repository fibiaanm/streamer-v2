<?php

use App\Domain\Workspaces\Http\Controllers\AcceptWorkspaceInvitationController;
use App\Domain\Workspaces\Http\Controllers\AncestorsController;
use App\Domain\Workspaces\Http\Controllers\ArchiveWorkspaceController;
use App\Domain\Workspaces\Http\Controllers\DetailController;
use App\Domain\Workspaces\Http\Controllers\AssignRoleController;
use App\Domain\Workspaces\Http\Controllers\CapabilitiesController;
use App\Domain\Workspaces\Http\Controllers\CreateRoleController;
use App\Domain\Workspaces\Http\Controllers\CreateWorkspaceController;
use App\Domain\Workspaces\Http\Controllers\DeleteRoleController;
use App\Domain\Workspaces\Http\Controllers\DeleteWorkspaceController;
use App\Domain\Workspaces\Http\Controllers\InviteMemberController;
use App\Domain\Workspaces\Http\Controllers\ListChildrenController;
use App\Domain\Workspaces\Http\Controllers\ListMembersController;
use App\Domain\Workspaces\Http\Controllers\ListRolesController;
use App\Domain\Workspaces\Http\Controllers\ListWorkspacesController;
use App\Domain\Workspaces\Http\Controllers\RemoveMemberController;
use App\Domain\Workspaces\Http\Controllers\RootQuotaController;
use App\Domain\Workspaces\Http\Controllers\ShowWorkspaceController;
use App\Domain\Workspaces\Http\Controllers\ShowWorkspaceInvitationController;
use App\Domain\Workspaces\Http\Controllers\UpdateWorkspaceController;
use Illuminate\Support\Facades\Route;

// Public — invitation flow (no auth required)
Route::prefix('workspaces/invitations')->group(function () {
    Route::get('{token}',         ShowWorkspaceInvitationController::class);
    Route::post('{token}/accept', AcceptWorkspaceInvitationController::class);
});

// Authenticated + active enterprise
Route::prefix('workspaces')->middleware(['auth.jwt', 'enterprise'])->group(function () {
    Route::get('root-quota', RootQuotaController::class); // must be before {workspaceId}

    Route::get('/',  ListWorkspacesController::class);
    Route::post('/', CreateWorkspaceController::class);

    Route::prefix('{workspaceId}')->group(function () {
        Route::get('/',        ShowWorkspaceController::class);
        Route::patch('/',      UpdateWorkspaceController::class);
        Route::delete('/',     DeleteWorkspaceController::class);
        Route::patch('archive', ArchiveWorkspaceController::class);

        Route::get('detail',       DetailController::class);
        Route::get('ancestors',    AncestorsController::class);
        Route::get('children',     ListChildrenController::class);
        Route::get('capabilities', CapabilitiesController::class);

        // Members
        Route::get('members',                           ListMembersController::class);
        Route::post('invitations',                      InviteMemberController::class);
        Route::delete('members/{memberId}',             RemoveMemberController::class);
        Route::patch('members/{memberId}/role',         AssignRoleController::class);

        // Roles
        Route::get('roles',          ListRolesController::class);
        Route::post('roles',         CreateRoleController::class);
        Route::delete('roles/{roleId}', DeleteRoleController::class);
    });
});
