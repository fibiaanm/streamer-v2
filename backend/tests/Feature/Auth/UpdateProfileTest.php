<?php

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Helpers ───────────────────────────────────────────────────────────────────

function profileAuthHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        'Accept'        => 'application/json',
    ];
}

// ── Caso feliz ────────────────────────────────────────────────────────────────

it('updates the user name', function () {
    $user = User::factory()->create(['name' => 'Original']);

    $this->withHeaders(profileAuthHeaders($user))
        ->patchJson('/api/v1/auth/profile', ['name' => 'Nuevo Nombre'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nuevo Nombre')
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'avatar_url']]);

    expect($user->fresh()->name)->toBe('Nuevo Nombre');
});

it('returns avatar_url with empty strings when no avatar is set', function () {
    $user = User::factory()->create();

    $this->withHeaders(profileAuthHeaders($user))
        ->patchJson('/api/v1/auth/profile', ['name' => $user->name])
        ->assertOk()
        ->assertJsonPath('data.avatar_url.jpeg', '')
        ->assertJsonPath('data.avatar_url.webp', '');
});

// ── Validación ────────────────────────────────────────────────────────────────

it('rejects empty name', function () {
    $user = User::factory()->create();

    $this->withHeaders(profileAuthHeaders($user))
        ->patchJson('/api/v1/auth/profile', ['name' => ''])
        ->assertUnprocessable();
});

it('rejects missing name', function () {
    $user = User::factory()->create();

    $this->withHeaders(profileAuthHeaders($user))
        ->patchJson('/api/v1/auth/profile', [])
        ->assertUnprocessable();
});

it('rejects name exceeding 255 characters', function () {
    $user = User::factory()->create();

    $this->withHeaders(profileAuthHeaders($user))
        ->patchJson('/api/v1/auth/profile', ['name' => str_repeat('a', 256)])
        ->assertUnprocessable();
});

// ── Sin autenticación ─────────────────────────────────────────────────────────

it('returns 401 without token', function () {
    $this->patchJson('/api/v1/auth/profile', ['name' => 'Test'])
        ->assertUnauthorized();
});
