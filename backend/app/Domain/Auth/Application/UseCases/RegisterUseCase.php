<?php

namespace App\Domain\Auth\Application\UseCases;

use App\Domain\Auth\Application\TokenService;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class RegisterUseCase
{
    public function __construct(private TokenService $tokenService) {}

    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            do {
                $friendCode = strtoupper(Str::random(8));
            } while (User::where('friend_code', $friendCode)->exists());

            $user = User::create([
                'name'        => $data['name'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'friend_code' => $friendCode,
            ]);

            $enterprise = $user->createEnterprise($user->name);
            $freePlan   = Plan::freeFor('core');
            $enterprise->createEnterpriseProduct($freePlan);
            $user->assignOwnerRole($enterprise);

            return $this->tokenService->issueTokens($user);
        });
    }
}
