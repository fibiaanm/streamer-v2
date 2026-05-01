<?php

namespace App\Domain\Auth\Http\Resources;

use App\Services\LimitsResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $enterprise        = $request->attributes->get('active_enterprise');
        $member            = $request->attributes->get('active_enterprise_member');
        $enterpriseProducts = $request->attributes->get('active_enterprise_products');

        $permissions = $member->role
            ? $member->role->permissions->pluck('name')->all()
            : [];

        $products = $enterpriseProducts->isNotEmpty()
            ? app(LimitsResolver::class)->byProduct($enterpriseProducts)
            : null;

        return [
            'id'         => $this->getHashId(),
            'name'       => $this->name,
            'email'      => $this->email,
            'is_admin'   => (bool) $this->is_admin,
            'avatar_url' => $this->getAvatarUrls(),
            'enterprise' => [
                'id'          => $enterprise->getHashId(),
                'name'        => $enterprise->name,
                'type'        => $enterprise->type,
                'role'        => $member->role?->name,
                'permissions' => $permissions,
                'products'    => $products,
            ],
        ];
    }
}
