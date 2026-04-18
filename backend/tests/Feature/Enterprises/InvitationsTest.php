<?php

use App\Exceptions\ErrorCode;
use App\Models\EnterpriseMember;
use App\Models\EnterprisePermission;
use App\Models\EnterpriseRole;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Helpers ───────────────────────────────────────────────────────────────────

function inviteContext(): array
{
    $owner      = User::factory()->create();
    $enterprise = $owner->enterpriseMembers()->first()->enterprise;
    $token      = JWTAuth::fromUser($owner);

    return [$owner, $enterprise, $token];
}

function inviteMemberIn(\App\Models\Enterprise $enterprise, string $roleName): array
{
    $user = User::factory()->create();
    $role = EnterpriseRole::whereNull('enterprise_id')->where('name', $roleName)->firstOrFail();

    EnterpriseMember::create([
        'user_id'       => $user->id,
        'enterprise_id' => $enterprise->id,
        'role_id'       => $role->id,
        'status'        => 'active',
    ]);

    return [$user, JWTAuth::fromUser($user)];
}

// ── Happy path ────────────────────────────────────────────────────────────────

it('invites with default member role when no role_id given', function () {
    Event::fake();
    Queue::fake();
    [, $enterprise, $token] = inviteContext();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', [
        'emails' => ['alice@example.com'],
    ])
        ->assertCreated()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.role.name', 'member');
});

it('invites with a custom role_id', function () {
    Event::fake();
    Queue::fake();
    [, $enterprise, $token] = inviteContext();

    // Create a custom role with a subset of owner's permissions
    $perm       = EnterprisePermission::where('name', 'enterprise.settings.view')->firstOrFail();
    $customRole = EnterpriseRole::create([
        'enterprise_id' => $enterprise->id,
        'name'          => 'viewer',
        'is_default'    => false,
    ]);
    $customRole->permissions()->attach($perm->id);

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', [
        'emails'  => ['bob@example.com'],
        'role_id' => $customRole->getHashId(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.role.name', 'viewer');
});

it('invites with global admin role', function () {
    Event::fake();
    Queue::fake();
    [, $enterprise, $token] = inviteContext();

    $adminRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'admin')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', [
        'emails'  => ['carol@example.com'],
        'role_id' => $adminRole->getHashId(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.role.name', 'admin');
});

it('refreshes a pending invitation updating the role', function () {
    Event::fake();
    Queue::fake();
    [, $enterprise, $token] = inviteContext();

    // First invite with member role
    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', [
        'emails' => ['dave@example.com'],
    ])->assertCreated();

    $adminRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'admin')->firstOrFail();

    // Re-invite with admin role — should refresh and update role
    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', [
        'emails'  => ['dave@example.com'],
        'role_id' => $adminRole->getHashId(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.role.name', 'admin');

    $this->assertDatabaseCount('invitations', 1);
});

// ── Errores esperados ─────────────────────────────────────────────────────────

it('returns 403 when role has more permissions than inviter (subset fail)', function () {
    [, $enterprise]      = inviteContext();
    [, $adminToken]      = inviteMemberIn($enterprise, 'admin');

    // billing role has enterprise.billing.manage which admin lacks
    $billingRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'billing')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $adminToken",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', [
        'emails'  => ['eve@example.com'],
        'role_id' => $billingRole->getHashId(),
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleAssignNotAllowed->value);
});

it('returns 404 when role_id does not resolve', function () {
    [, $enterprise, $token] = inviteContext();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', [
        'emails'  => ['frank@example.com'],
        'role_id' => 'nonexistent',
    ])
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleNotFound->value);
});

it('returns 422 when trying to invite with owner role', function () {
    [, $enterprise, $token] = inviteContext();

    $ownerRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'owner')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', [
        'emails'  => ['grace@example.com'],
        'role_id' => $ownerRole->getHashId(),
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleBaseImmutable->value);
});

it('returns 422 when emails array is empty', function () {
    [, $enterprise, $token] = inviteContext();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->postJson('/api/v1/enterprises/current/invitations', ['emails' => []])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});
