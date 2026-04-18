<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Events\RoleUpdated;
use App\Domain\Enterprises\Exceptions\EnterpriseActionForbiddenException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleBaseImmutableException;
use App\Domain\Enterprises\Exceptions\EnterpriseRoleNotFoundException;
use App\Domain\Enterprises\Http\Resources\RoleResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterprisePermission;
use App\Models\EnterpriseRole;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateRoleController
{
    public function __invoke(Request $request, string $roleId): JsonResponse
    {
        $request->validate([
            'name'          => ['sometimes', 'string', 'max:64'],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ]);

        try {
            $enterprise    = $request->attributes->get('active_enterprise');
            $currentMember = $request->attributes->get('active_enterprise_member');
            $currentMember->loadMissing('role.permissions');

            if (!$currentMember->role->permissions->pluck('name')->contains('enterprise.roles.edit')) {
                throw new EnterpriseActionForbiddenException();
            }

            $id = HashId::decode($roleId);

            $role = $id ? EnterpriseRole::find($id) : null;

            if (!$role || ($role->enterprise_id !== null && $role->enterprise_id !== $enterprise->id)) {
                throw new EnterpriseRoleNotFoundException();
            }

            if ($role->isOwner()) {
                throw new EnterpriseRoleBaseImmutableException();
            }

            if ($request->filled('name')) {
                $role->name = $request->input('name');
                $role->save();
            }

            if ($request->has('permissions')) {
                $ids = EnterprisePermission::whereIn('name', $request->input('permissions'))->pluck('id');
                $role->permissions()->sync($ids);
            }

            $role->load('permissions');
            event(new RoleUpdated($enterprise, $role));

            return ResponseFormatter::success(new RoleResource($role));

        } catch (EnterpriseActionForbiddenException | EnterpriseRoleNotFoundException | EnterpriseRoleBaseImmutableException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('enterprises.update_role_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
