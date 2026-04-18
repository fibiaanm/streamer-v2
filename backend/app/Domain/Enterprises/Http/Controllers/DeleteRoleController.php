<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Events\RoleDeleted;
use App\Domain\Enterprises\Exceptions\EnterpriseActionForbiddenException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleBaseImmutableException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleHasMembersException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleNotFoundException;
use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteRoleController
{
    public function __invoke(Request $request, string $roleId): JsonResponse
    {
        try {
            $enterprise    = $request->attributes->get('active_enterprise');
            $currentMember = $request->attributes->get('active_enterprise_member');
            $currentMember->loadMissing('role.permissions');

            if (!$currentMember->role->permissions->pluck('name')->contains('enterprise.roles.remove')) {
                throw new EnterpriseActionForbiddenException();
            }

            $id = HashId::decode($roleId);

            $role = $id ? EnterpriseRole::find($id) : null;

            if (!$role || ($role->enterprise_id !== null && $role->enterprise_id !== $enterprise->id)) {
                throw new EnterpriseRoleNotFoundException();
            }

            if ($role->isGlobal()) {
                throw new EnterpriseRoleBaseImmutableException();
            }

            $hasMembers = EnterpriseMember::where('enterprise_id', $enterprise->id)
                ->where('role_id', $role->id)
                ->exists();

            if ($hasMembers) {
                throw new EnterpriseRoleHasMembersException();
            }

            event(new RoleDeleted($enterprise, $role));
            $role->permissions()->detach();
            $role->delete();

            return ResponseFormatter::noContent();

        } catch (EnterpriseActionForbiddenException | EnterpriseRoleNotFoundException | EnterpriseRoleBaseImmutableException | EnterpriseRoleHasMembersException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('enterprises.delete_role_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
