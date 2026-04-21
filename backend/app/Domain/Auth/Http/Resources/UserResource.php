<?php

namespace App\Domain\Auth\Http\Resources;

use App\Services\LimitsResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $enterprise   = $request->attributes->get('active_enterprise');
        $member       = $request->attributes->get('active_enterprise_member');
        $subscription = $request->attributes->get('active_subscription');

        $permissions = $member->role
            ? $member->role->permissions->pluck('name')->all()
            : [];

        $planData = null;
        if ($subscription) {
            $limits = app(LimitsResolver::class)->resolve($subscription);
            $planData = [
                'name'   => $subscription->plan->name,
                'limits' => $limits,
            ];
        }

        return [
            'id'         => $this->getHashId(),
            'name'       => $this->name,
            'email'      => $this->email,
            'avatar_url' => $this->getAvatarUrls(),
            'enterprise' => [
                'id'          => $enterprise->getHashId(),
                'name'        => $enterprise->name,
                'type'        => $enterprise->type,
                'role'        => $member->role?->name,
                'permissions' => $permissions,
                'plan'        => $planData,
            ],
        ];
    }
}
