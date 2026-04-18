<?php

use App\Exceptions\ErrorCode;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Helpers ───────────────────────────────────────────────────────────────────

function rolesContext(): array
{
    $owner      = User::factory()->create();
    $enterprise = $owner->enterpriseMembers()->first()->enterprise;
    $token      = JWTAuth::fromUser($owner);

    return [$owner, $enterprise, $token];
}

function memberInEnterprise(\App\Models\Enterprise $enterprise, string $roleName): array
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

// ── CreateRole ────────────────────────────────────────────────────────────────

describe('CreateRole', function () {
    it('creates a role as admin', function () {
        Event::fake();
        [, $enterprise, $token] = rolesContext();
        [$admin, $adminToken]   = memberInEnterprise($enterprise, 'admin');

        $this->withHeaders([
            'Authorization'   => "Bearer $adminToken",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->postJson('/api/v1/enterprises/current/roles', [
            'name'        => 'editor',
            'permissions' => ['enterprise.settings.view'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'editor')
            ->assertJsonFragment(['enterprise.settings.view']);
    });

    it('creates a role as owner', function () {
        Event::fake();
        [, $enterprise, $token] = rolesContext();

        $this->withHeaders([
            'Authorization'   => "Bearer $token",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->postJson('/api/v1/enterprises/current/roles', ['name' => 'custom'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'custom');
    });

    it('returns 403 when member lacks enterprise.roles.add', function () {
        [, $enterprise]       = rolesContext();
        [$member, $memberToken] = memberInEnterprise($enterprise, 'member');

        $this->withHeaders([
            'Authorization'   => "Bearer $memberToken",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->postJson('/api/v1/enterprises/current/roles', ['name' => 'test'])
            ->assertForbidden()
            ->assertJsonPath('error.code', ErrorCode::EnterpriseForbidden->value);
    });

    it('returns 422 on missing name', function () {
        [, $enterprise, $token] = rolesContext();

        $this->withHeaders([
            'Authorization'   => "Bearer $token",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->postJson('/api/v1/enterprises/current/roles', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
    });
});

// ── UpdateRole ────────────────────────────────────────────────────────────────

describe('UpdateRole', function () {
    it('updates a custom role as owner', function () {
        Event::fake();
        [, $enterprise, $token] = rolesContext();

        $role = EnterpriseRole::create([
            'enterprise_id' => $enterprise->id,
            'name'          => 'before',
            'is_default'    => false,
        ]);

        $this->withHeaders([
            'Authorization'   => "Bearer $token",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->patchJson("/api/v1/enterprises/current/roles/{$role->getHashId()}", [
            'name' => 'after',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'after');
    });

    it('returns 403 when member lacks enterprise.roles.edit', function () {
        [, $enterprise]         = rolesContext();
        [$member, $memberToken] = memberInEnterprise($enterprise, 'member');

        $role = EnterpriseRole::create([
            'enterprise_id' => $enterprise->id,
            'name'          => 'some-role',
            'is_default'    => false,
        ]);

        $this->withHeaders([
            'Authorization'   => "Bearer $memberToken",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->patchJson("/api/v1/enterprises/current/roles/{$role->getHashId()}", [
            'name' => 'changed',
        ])
            ->assertForbidden()
            ->assertJsonPath('error.code', ErrorCode::EnterpriseForbidden->value);
    });

    it('returns 404 when role does not exist', function () {
        [, $enterprise, $token] = rolesContext();

        $this->withHeaders([
            'Authorization'   => "Bearer $token",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->patchJson('/api/v1/enterprises/current/roles/nonexistent', ['name' => 'x'])
            ->assertNotFound()
            ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleNotFound->value);
    });

    it('returns 422 when updating a global role', function () {
        [, $enterprise, $token] = rolesContext();

        $ownerRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'owner')->firstOrFail();

        $this->withHeaders([
            'Authorization'   => "Bearer $token",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->patchJson("/api/v1/enterprises/current/roles/{$ownerRole->getHashId()}", [
            'name' => 'hacked',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleBaseImmutable->value);
    });
});

// ── DeleteRole ────────────────────────────────────────────────────────────────

describe('DeleteRole', function () {
    it('deletes a custom role as owner', function () {
        Event::fake();
        [, $enterprise, $token] = rolesContext();

        $role = EnterpriseRole::create([
            'enterprise_id' => $enterprise->id,
            'name'          => 'to-delete',
            'is_default'    => false,
        ]);

        $this->withHeaders([
            'Authorization'   => "Bearer $token",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->deleteJson("/api/v1/enterprises/current/roles/{$role->getHashId()}")
            ->assertNoContent();

        $this->assertDatabaseMissing('enterprise_roles', ['id' => $role->id]);
    });

    it('returns 403 when member lacks enterprise.roles.remove', function () {
        [, $enterprise]         = rolesContext();
        [$member, $memberToken] = memberInEnterprise($enterprise, 'member');

        $role = EnterpriseRole::create([
            'enterprise_id' => $enterprise->id,
            'name'          => 'protected',
            'is_default'    => false,
        ]);

        $this->withHeaders([
            'Authorization'   => "Bearer $memberToken",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->deleteJson("/api/v1/enterprises/current/roles/{$role->getHashId()}")
            ->assertForbidden()
            ->assertJsonPath('error.code', ErrorCode::EnterpriseForbidden->value);
    });

    it('returns 422 when role has active members', function () {
        [, $enterprise, $token] = rolesContext();

        $role = EnterpriseRole::create([
            'enterprise_id' => $enterprise->id,
            'name'          => 'in-use',
            'is_default'    => false,
        ]);

        $member = User::factory()->create();
        EnterpriseMember::create([
            'user_id'       => $member->id,
            'enterprise_id' => $enterprise->id,
            'role_id'       => $role->id,
            'status'        => 'active',
        ]);

        $this->withHeaders([
            'Authorization'   => "Bearer $token",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->deleteJson("/api/v1/enterprises/current/roles/{$role->getHashId()}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleHasMembers->value);
    });

    it('returns 422 when trying to delete a global role', function () {
        [, $enterprise, $token] = rolesContext();

        $adminRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'admin')->firstOrFail();

        $this->withHeaders([
            'Authorization'   => "Bearer $token",
            'X-Enterprise-ID' => $enterprise->getHashId(),
        ])->deleteJson("/api/v1/enterprises/current/roles/{$adminRole->getHashId()}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', ErrorCode::EnterpriseRoleBaseImmutable->value);
    });
});
