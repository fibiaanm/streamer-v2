<?php

use App\Exceptions\ErrorCode;
use App\Models\EnterpriseMember;
use App\Models\EnterprisePermission;
use App\Models\EnterpriseRole;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Helpers ───────────────────────────────────────────────────────────────────

function assignContext(): array
{
    $owner      = User::factory()->create();
    $enterprise = $owner->enterpriseMembers()->first()->enterprise;
    $token      = JWTAuth::fromUser($owner);

    return [$owner, $enterprise, $token];
}

function addMemberWithRole(\App\Models\Enterprise $enterprise, string $roleName): array
{
    $user = User::factory()->create();
    $role = EnterpriseRole::whereNull('enterprise_id')->where('name', $roleName)->firstOrFail();

    EnterpriseMember::create([
        'user_id'       => $user->id,
        'enterprise_id' => $enterprise->id,
        'role_id'       => $role->id,
        'status'        => 'active',
    ]);

    $member = EnterpriseMember::where('user_id', $user->id)
        ->where('enterprise_id', $enterprise->id)
        ->first();

    return [$user, $member, JWTAuth::fromUser($user)];
}

// ── Tests ─────────────────────────────────────────────────────────────────────

it('owner assigns admin role to a member', function () {
    Event::fake();
    [, $enterprise, $token] = assignContext();
    [, $target]             = addMemberWithRole($enterprise, 'member');

    $adminRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'admin')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->patchJson("/api/v1/enterprises/current/members/{$target->getHashId()}/role", [
        'role_id' => $adminRole->getHashId(),
    ])
        ->assertOk()
        ->assertJsonPath('data.role.name', 'admin');
});

it('admin assigns member role (subset check passes)', function () {
    Event::fake();
    [, $enterprise]          = assignContext();
    [, , $adminToken]        = addMemberWithRole($enterprise, 'admin');
    [, $target]              = addMemberWithRole($enterprise, 'member');

    // Create a custom role with only permissions admin has
    $perm        = EnterprisePermission::where('name', 'enterprise.settings.view')->firstOrFail();
    $customRole  = EnterpriseRole::create([
        'enterprise_id' => $enterprise->id,
        'name'          => 'viewer',
        'is_default'    => false,
    ]);
    $customRole->permissions()->attach($perm->id);

    $this->withHeaders([
        'Authorization'   => "Bearer $adminToken",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->patchJson("/api/v1/enterprises/current/members/{$target->getHashId()}/role", [
        'role_id' => $customRole->getHashId(),
    ])
        ->assertOk()
        ->assertJsonPath('data.role.name', 'viewer');
});

it('returns 403 when member lacks enterprise.roles.assign', function () {
    [, $enterprise]          = assignContext();
    [, , $memberToken]       = addMemberWithRole($enterprise, 'member');
    [, $target]              = addMemberWithRole($enterprise, 'member');

    $adminRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'admin')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $memberToken",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->patchJson("/api/v1/enterprises/current/members/{$target->getHashId()}/role", [
        'role_id' => $adminRole->getHashId(),
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleAssignNotAllowed->value);
});

it('returns 403 when target role has permissions not in assigner role (subset fail)', function () {
    [, $enterprise]   = assignContext();
    [, , $adminToken] = addMemberWithRole($enterprise, 'admin');
    [, $target]       = addMemberWithRole($enterprise, 'member');

    // billing role has enterprise.billing.manage which admin lacks
    $billingRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'billing')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $adminToken",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->patchJson("/api/v1/enterprises/current/members/{$target->getHashId()}/role", [
        'role_id' => $billingRole->getHashId(),
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleAssignNotAllowed->value);
});

it('returns 404 when target member does not exist', function () {
    [, $enterprise, $token] = assignContext();

    $adminRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'admin')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->patchJson('/api/v1/enterprises/current/members/nonexistent/role', [
        'role_id' => $adminRole->getHashId(),
    ])
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleNotFound->value);
});

it('returns 422 when assigning to self', function () {
    [, $enterprise, $token] = assignContext();

    $ownerMember = EnterpriseMember::where('enterprise_id', $enterprise->id)->firstOrFail();
    $adminRole   = EnterpriseRole::whereNull('enterprise_id')->where('name', 'admin')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->patchJson("/api/v1/enterprises/current/members/{$ownerMember->getHashId()}/role", [
        'role_id' => $adminRole->getHashId(),
    ])
        ->assertStatus(422);
});

it('returns 422 when target member is owner', function () {
    Event::fake();
    [$owner, $enterprise, $token] = assignContext();

    // Second owner-role member
    $ownerRole   = EnterpriseRole::whereNull('enterprise_id')->where('name', 'owner')->firstOrFail();
    $secondUser  = User::factory()->create();
    $ownerMember = EnterpriseMember::create([
        'user_id'       => $secondUser->id,
        'enterprise_id' => $enterprise->id,
        'role_id'       => $ownerRole->id,
        'status'        => 'active',
    ]);

    $adminRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'admin')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->patchJson("/api/v1/enterprises/current/members/{$ownerMember->getHashId()}/role", [
        'role_id' => $adminRole->getHashId(),
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleBaseImmutable->value);
});

it('returns 422 when assigning the owner role', function () {
    [, $enterprise, $token] = assignContext();
    [, $target]             = addMemberWithRole($enterprise, 'member');

    $ownerRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'owner')->firstOrFail();

    $this->withHeaders([
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ])->patchJson("/api/v1/enterprises/current/members/{$target->getHashId()}/role", [
        'role_id' => $ownerRole->getHashId(),
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleBaseImmutable->value);
});
