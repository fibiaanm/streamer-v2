<?php

use App\Domain\Enterprises\Http\Controllers\CancelInvitationController;
use App\Domain\Enterprises\Http\Controllers\CreateInvitationsController;
use App\Domain\Enterprises\Http\Controllers\CreateRoleController;
use App\Domain\Enterprises\Http\Controllers\DeleteRoleController;
use App\Domain\Enterprises\Http\Controllers\ListInvitationsController;
use App\Domain\Enterprises\Http\Controllers\ListMembersController;
use App\Domain\Enterprises\Http\Controllers\ListPermissionsController;
use App\Domain\Enterprises\Http\Controllers\ListRolesController;
use App\Domain\Enterprises\Http\Controllers\RemoveMemberController;
use App\Domain\Enterprises\Http\Controllers\UpdateEnterpriseController;
use App\Domain\Enterprises\Http\Controllers\UpdateRoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('enterprises')->middleware(['auth.jwt', 'enterprise'])->group(function () {
    Route::prefix('current')->group(function () {
        Route::patch('/',                           UpdateEnterpriseController::class);
        Route::get('members',                       ListMembersController::class);
        Route::delete('members/{memberId}',         RemoveMemberController::class);
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
