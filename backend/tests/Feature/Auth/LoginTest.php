<?php

use App\Exceptions\ErrorCode;
use App\Models\User;

it('issues tokens on valid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'secret123',
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in']]);
});

it('returns 401 on wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct')]);

    $this->postJson('/api/v1/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', ErrorCode::AuthInvalidCredentials->value);
});

it('returns 401 on non-existent email', function () {
    $this->postJson('/api/v1/auth/login', [
        'email'    => 'ghost@example.com',
        'password' => 'whatever',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', ErrorCode::AuthInvalidCredentials->value);
    // Mismo código — no revelar si el email existe
});

it('returns 422 on missing fields', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});
