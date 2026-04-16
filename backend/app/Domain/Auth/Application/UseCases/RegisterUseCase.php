<?php

namespace App\Domain\Auth\Application\UseCases;

use App\Domain\Auth\Application\TokenService;
use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUseCase
{
    public function __construct(private TokenService $tokenService) {}

    public function execute(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Empresa silenciosa — refleja lo que hace UserFactory::afterCreating
        $enterprise = Enterprise::create([
            'name'     => $user->name,
            'type'     => 'personal',
            'owner_id' => $user->id,
        ]);

        $ownerRole = EnterpriseRole::where('name', 'owner')
            ->whereNull('enterprise_id')
            ->firstOrFail();

        EnterpriseMember::create([
            'user_id'       => $user->id,
            'enterprise_id' => $enterprise->id,
            'role_id'       => $ownerRole->id,
            'status'        => 'active',
        ]);

        $freePlan = Plan::where('name', 'Personal Free')->firstOrFail();

        Subscription::create([
            'enterprise_id' => $enterprise->id,
            'plan_id'       => $freePlan->id,
            'status'        => 'active',
            'starts_at'     => now(),
        ]);

        return $this->tokenService->issueTokens($user);
    }
}
