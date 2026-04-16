<?php

use App\Exceptions\ErrorCode;
use App\Models\Enterprise;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Caso feliz ────────────────────────────────────────────────────────────────

it('returns user with enterprise when fully authenticated', function () {
    $user = User::factory()->create();

    $member     = $user->enterpriseMembers()->with('enterprise')->first();
    $enterprise = $member->enterprise;
    $token      = JWTAuth::fromUser($user);

    $response = $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
        'Accept'          => 'application/json',
    ])->getJson('/api/v1/auth/me');

    $response
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.enterprise.id', $enterprise->getHashId())
        ->assertJsonPath('data.enterprise.role', 'owner')
        ->assertHeader('X-Request-ID');

    // owner has all 11 permissions
    $permissions = $response->json('data.enterprise.permissions');
    expect($permissions)->toBeArray()->not->toBeEmpty();

    // plan limits are present with correct structure
    $response
        ->assertJsonPath('data.enterprise.plan.name', 'Personal Free')
        ->assertJsonStructure([
            'data' => [
                'enterprise' => [
                    'plan' => [
                        'name',
                        'limits' => [
                            'members'            => ['type', 'max'],
                            'workspaces'         => ['type', 'max'],
                            'workspace_depth'    => ['type', 'max'],
                            'storage_gb'         => ['type', 'max'],
                            'streams_concurrent' => ['type', 'max'],
                            'stream_minutes'     => ['type', 'max'],
                            'rooms_concurrent'   => ['type', 'max'],
                            'room_participants'  => ['type', 'max'],
                            'room_guests'        => ['type', 'max'],
                        ],
                    ],
                ],
            ],
        ]);
});

// ── JWT inválido o ausente (401) ──────────────────────────────────────────────

it('returns 401 without token', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', ErrorCode::AuthUnauthorized->value);
});

it('returns 401 with malformed token', function () {
    $this->withHeaders(['Authorization' => 'Bearer not.a.real.token'])
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', ErrorCode::AuthUnauthorized->value);
});

it('returns 401 with valid signature but user not in database', function () {
    $ghost = User::factory()->make(['id' => 99999]);
    $token = JWTAuth::fromUser($ghost);

    $this->withHeaders([
        'Authorization' => "Bearer $token",
        'Accept'        => 'application/json',
    ])->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', ErrorCode::AuthUnauthorized->value);
});

// ── Empresa requerida ─────────────────────────────────────────────────────────

it('returns 422 when enterprise header is missing', function () {
    $user  = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->getJson('/api/v1/auth/me')
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::EnterpriseHeaderRequired->value);
});

it('returns 404 when enterprise hash does not resolve to a record', function () {
    $user  = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => 'invalidhash000',
    ])->getJson('/api/v1/auth/me')
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseNotFound->value);
});

it('returns 403 when user is not a member of the enterprise', function () {
    $user       = User::factory()->create();
    $enterprise = Enterprise::factory()->create();
    $token      = JWTAuth::fromUser($user);

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->getJson('/api/v1/auth/me')
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseNotMember->value);
});
