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

    $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshToken])
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
    $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshToken]);

    // Segunda rotación con el mismo token — ya revocado
    $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshToken])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', ErrorCode::AuthRefreshTokenInvalid->value);
});
