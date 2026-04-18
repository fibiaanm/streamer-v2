<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Events\RoleCreated;
use App\Domain\Enterprises\Http\Resources\RoleResource;
use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterprisePermission;
use App\Models\EnterpriseRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateRoleController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:64'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $role = EnterpriseRole::create([
                'enterprise_id' => $enterprise->id,
                'name'          => $request->input('name'),
                'is_default'    => false,
            ]);

            if ($request->filled('permissions')) {
                $ids = EnterprisePermission::whereIn('name', $request->input('permissions'))->pluck('id');
                $role->permissions()->sync($ids);
            }

            $role->load('permissions');
            event(new RoleCreated($enterprise, $role));

            return ResponseFormatter::created(new RoleResource($role));

        } catch (Throwable $e) {
            Log::error('enterprises.create_role_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
