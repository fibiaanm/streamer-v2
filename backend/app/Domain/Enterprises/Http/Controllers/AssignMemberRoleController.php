<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Events\MemberRoleChanged;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleAssignNotAllowedException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleBaseImmutableException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleNotFoundException;
use App\Domain\Enterprises\Http\Resources\MemberResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssignMemberRoleController
{
    public function __invoke(Request $request, string $memberId): JsonResponse
    {
        $request->validate(['role_id' => ['required', 'string']]);

        try {
            $enterprise    = $request->attributes->get('active_enterprise');
            $currentMember = $request->attributes->get('active_enterprise_member');
            $currentMember->loadMissing('role.permissions');

            if (!$currentMember->role->permissions->pluck('name')->contains('enterprise.roles.assign')) {
                throw new EnterpriseRoleAssignNotAllowedException();
            }

            $target = EnterpriseMember::findByHashId($memberId);

            if (!$target || $target->enterprise_id !== $enterprise->id) {
                throw new EnterpriseRoleNotFoundException();
            }

            if ($target->id === $currentMember->id) {
                return response()->json([
                    'error' => ['code' => 'enterprise.cannot_assign_self', 'context' => []],
                ], 422);
            }

            $target->loadMissing('role');
            if ($target->role->isOwner()) {
                throw new EnterpriseRoleBaseImmutableException();
            }

            $roleId = HashId::decode($request->input('role_id'));
            $role   = $roleId ? EnterpriseRole::find($roleId) : null;

            if (!$role || ($role->enterprise_id !== null && $role->enterprise_id !== $enterprise->id)) {
                throw new EnterpriseRoleNotFoundException();
            }

            if ($role->isOwner()) {
                throw new EnterpriseRoleBaseImmutableException();
            }

            // Subset check: role's permissions must be a subset of current user's permissions
            $role->loadMissing('permissions');
            $myPerms   = $currentMember->role->permissions->pluck('name')->all();
            $rolePerms = $role->permissions->pluck('name')->all();

            if (!empty(array_diff($rolePerms, $myPerms))) {
                throw new EnterpriseRoleAssignNotAllowedException();
            }

            $target->role_id = $role->id;
            $target->save();
            $target->load('role');

            event(new MemberRoleChanged($enterprise, $target, $role));

            return ResponseFormatter::success(new MemberResource($target));

        } catch (EnterpriseRoleNotFoundException | EnterpriseRoleBaseImmutableException | EnterpriseRoleAssignNotAllowedException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('enterprises.assign_member_role_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
