<?php

use App\Exceptions\ErrorCode;
use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Str;

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeInvitation(array $overrides = []): Invitation
{
    $owner      = User::factory()->create();
    $enterprise = $owner->enterpriseMembers()->first()->enterprise;
    $role       = EnterpriseRole::whereNull('enterprise_id')->where('name', 'member')->firstOrFail();

    return Invitation::create(array_merge([
        'invitable_type'     => Enterprise::class,
        'invitable_id'       => $enterprise->id,
        'invited_by_user_id' => $owner->id,
        'email'              => 'invited@example.com',
        'enterprise_role_id' => $role->id,
        'token'              => Str::uuid()->toString(),
        'status'             => 'pending',
        'expires_at'         => now()->addDays(7),
    ], $overrides));
}

// ── Show invitation ───────────────────────────────────────────────────────────

it('shows invitation info for a new user', function () {
    $invitation = makeInvitation();

    $this->getJson("/api/v1/invitations/{$invitation->token}")
        ->assertOk()
        ->assertJsonPath('data.email', 'invited@example.com')
        ->assertJsonPath('data.user_exists', false)
        ->assertJsonStructure(['data' => ['email', 'enterprise_name', 'role_name', 'user_exists']]);
});

it('shows invitation info for an existing user', function () {
    User::factory()->create(['email' => 'invited@example.com']);
    $invitation = makeInvitation();

    $this->getJson("/api/v1/invitations/{$invitation->token}")
        ->assertOk()
        ->assertJsonPath('data.user_exists', true);
});

it('returns 404 for an unknown token', function () {
    $this->getJson('/api/v1/invitations/nonexistent-token')
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseInvitationInvalid->value);
});

it('returns 422 for an expired invitation', function () {
    $invitation = makeInvitation(['expires_at' => now()->subDay()]);

    $this->getJson("/api/v1/invitations/{$invitation->token}")
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::EnterpriseInvitationExpired->value);
});

it('returns 404 for an already accepted invitation', function () {
    $invitation = makeInvitation(['status' => 'accepted']);

    $this->getJson("/api/v1/invitations/{$invitation->token}")
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseInvitationInvalid->value);
});

// ── Accept — new user ─────────────────────────────────────────────────────────

it('creates user and membership when accepting as new user', function () {
    $invitation = makeInvitation();

    $this->postJson("/api/v1/invitations/{$invitation->token}/accept", [
        'name'     => 'Alice',
        'password' => 'password123',
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in']]);

    $user = User::where('email', 'invited@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Alice');

    expect(
        EnterpriseMember::where('user_id', $user->id)
            ->where('enterprise_id', $invitation->invitable_id)
            ->exists()
    )->toBeTrue();

    expect($invitation->fresh()->status)->toBe('accepted');
});

it('creates a personal enterprise for a new user when accepting', function () {
    $invitation = makeInvitation();

    $this->postJson("/api/v1/invitations/{$invitation->token}/accept", [
        'name'     => 'Alice',
        'password' => 'password123',
    ])->assertOk();

    $user = User::where('email', 'invited@example.com')->first();
    expect($user->enterpriseMembers()->count())->toBe(2);
});

// ── Accept — existing user ────────────────────────────────────────────────────

it('adds existing user to enterprise when accepting with correct password', function () {
    $existing   = User::factory()->create(['email' => 'invited@example.com']);
    $invitation = makeInvitation();

    $this->postJson("/api/v1/invitations/{$invitation->token}/accept", [
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in']]);

    expect(
        EnterpriseMember::where('user_id', $existing->id)
            ->where('enterprise_id', $invitation->invitable_id)
            ->exists()
    )->toBeTrue();
});

// ── Errores esperados ─────────────────────────────────────────────────────────

it('returns 401 when existing user provides wrong password', function () {
    User::factory()->create(['email' => 'invited@example.com']);
    $invitation = makeInvitation();

    $this->postJson("/api/v1/invitations/{$invitation->token}/accept", [
        'password' => 'wrong-password',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', ErrorCode::AuthInvalidCredentials->value);
});

it('returns 422 when name is missing for a new user', function () {
    $invitation = makeInvitation();

    $this->postJson("/api/v1/invitations/{$invitation->token}/accept", [
        'password' => 'password123',
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 if user is already an active member', function () {
    $existing   = User::factory()->create(['email' => 'invited@example.com']);
    $invitation = makeInvitation();

    EnterpriseMember::create([
        'user_id'       => $existing->id,
        'enterprise_id' => $invitation->invitable_id,
        'role_id'       => $invitation->enterprise_role_id,
        'status'        => 'active',
    ]);

    $this->postJson("/api/v1/invitations/{$invitation->token}/accept", [
        'password' => 'password',
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::EnterpriseMemberExists->value);
});

it('reactivates a suspended member with the invited role', function () {
    $existing   = User::factory()->create(['email' => 'invited@example.com']);
    $invitation = makeInvitation();

    EnterpriseMember::create([
        'user_id'       => $existing->id,
        'enterprise_id' => $invitation->invitable_id,
        'role_id'       => $invitation->enterprise_role_id,
        'status'        => 'suspended',
    ]);

    $this->postJson("/api/v1/invitations/{$invitation->token}/accept", [
        'password' => 'password',
    ])->assertOk();

    $member = EnterpriseMember::where('user_id', $existing->id)
        ->where('enterprise_id', $invitation->invitable_id)
        ->first();

    expect($member->status)->toBe('active');
    expect(EnterpriseMember::where('user_id', $existing->id)
        ->where('enterprise_id', $invitation->invitable_id)
        ->count()
    )->toBe(1);
});

it('returns 404 when token does not exist', function () {
    $this->postJson('/api/v1/invitations/bad-token/accept', [
        'name'     => 'Alice',
        'password' => 'password123',
    ])
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseInvitationInvalid->value);
});

it('returns 422 when invitation is expired', function () {
    $invitation = makeInvitation(['expires_at' => now()->subDay()]);

    $this->postJson("/api/v1/invitations/{$invitation->token}/accept", [
        'name'     => 'Alice',
        'password' => 'password123',
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::EnterpriseInvitationExpired->value);
});
