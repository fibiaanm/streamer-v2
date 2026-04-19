<?php

use App\Domain\Enterprises\Http\Controllers\AcceptInvitationController;
use App\Domain\Enterprises\Http\Controllers\AssignMemberRoleController;
use App\Domain\Enterprises\Http\Controllers\CancelInvitationController;
use App\Domain\Enterprises\Http\Controllers\CreateInvitationsController;
use App\Domain\Enterprises\Http\Controllers\CreateRoleController;
use App\Domain\Enterprises\Http\Controllers\DeleteRoleController;
use App\Domain\Enterprises\Http\Controllers\ListInvitationsController;
use App\Domain\Enterprises\Http\Controllers\ListMembersController;
use App\Domain\Enterprises\Http\Controllers\ListPermissionsController;
use App\Domain\Enterprises\Http\Controllers\ListRolesController;
use App\Domain\Enterprises\Http\Controllers\RemoveMemberController;
use App\Domain\Enterprises\Http\Controllers\ShowInvitationController;
use App\Domain\Enterprises\Http\Controllers\UpdateEnterpriseController;
use App\Domain\Enterprises\Http\Controllers\UpdateRoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('invitations')->group(function () {
    Route::get('{token}',         ShowInvitationController::class);
    Route::post('{token}/accept', AcceptInvitationController::class);
});

Route::prefix('enterprises')->middleware(['auth.jwt', 'enterprise'])->group(function () {
    Route::prefix('current')->group(function () {
        Route::patch('/',                           UpdateEnterpriseController::class);
        Route::get('members',                           ListMembersController::class);
        Route::delete('members/{memberId}',             RemoveMemberController::class);
        Route::patch('members/{memberId}/role',         AssignMemberRoleController::class);
        Route::get('invitations',                   ListInvitationsController::class);
        Route::post('invitations',                  CreateInvitationsController::class);
        Route::delete('invitations/{invitationId}', CancelInvitationController::class);

        Route::get('permissions', ListPermissionsController::class);
        Route::get('roles',       ListRolesController::class);
        Route::post('roles',      CreateRoleController::class);
        Route::patch('roles/{roleId}',  UpdateRoleController::class);
        Route::delete('roles/{roleId}', DeleteRoleController::class);
    });
});
