<?php

namespace App\Domain\Auth\Application\UseCases;

use App\Domain\Auth\Application\TokenService;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class RegisterUseCase
{
    public function __construct(private TokenService $tokenService) {}

    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $enterprise = $user->createEnterprise($user->name);
            $freePlan   = Plan::where('name', 'Free')->firstOrFail();
            $enterprise->createSubscription($freePlan);
            $user->assignOwnerRole($enterprise);

            return $this->tokenService->issueTokens($user);
        });
    }
}
