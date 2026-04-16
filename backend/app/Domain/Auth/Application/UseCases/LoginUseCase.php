<?php

namespace App\Domain\Auth\Application\UseCases;

use App\Domain\Auth\Application\TokenService;
use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginUseCase
{
    public function __construct(private TokenService $tokenService) {}

    public function execute(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenService->issueTokens($user);
    }
}
