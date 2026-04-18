<?php

use App\Exceptions\ErrorCode;
use App\Models\User;

it('rotates refresh token and issues new tokens', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $refreshToken = $loginResponse->json('data.refresh_token');

    $this->withCredentials()
        ->withUnencryptedCookie('refresh_token', $refreshToken)
        ->postJson('/api/v1/auth/refresh')
        ->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in']]);
});

it('returns 401 on already-used refresh token', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $refreshToken = $loginResponse->json('data.refresh_token');

    // Primera rotación — válida
    $this->withCredentials()
        ->withUnencryptedCookie('refresh_token', $refreshToken)
        ->postJson('/api/v1/auth/refresh');

    // Segunda rotación con el mismo token — ya revocado
    $this->withCredentials()
        ->withUnencryptedCookie('refresh_token', $refreshToken)
        ->postJson('/api/v1/auth/refresh')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', ErrorCode::AuthRefreshTokenInvalid->value);
});
