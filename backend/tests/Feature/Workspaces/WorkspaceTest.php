<?php

use App\Exceptions\ErrorCode;
use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspacePermission;
use App\Models\WorkspaceRole;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Helpers ───────────────────────────────────────────────────────────────────

function wsCtx(): array
{
    $owner      = User::factory()->create();
    $member     = $owner->enterpriseMembers()->first();
    $enterprise = $member->enterprise;
    $token      = JWTAuth::fromUser($owner);

    return [$owner, $enterprise, $token];
}

function wsHdr(string $token, Enterprise $enterprise): array
{
    return [
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ];
}

function addToEnt(User $user, Enterprise $enterprise): void
{
    $memberRole = EnterpriseRole::whereNull('enterprise_id')->where('name', 'member')->firstOrFail();
    EnterpriseMember::create([
        'user_id'       => $user->id,
        'enterprise_id' => $enterprise->id,
        'role_id'       => $memberRole->id,
        'status'        => 'active',
    ]);
}

function createWs(Enterprise $enterprise, User $owner): Workspace
{
    $ws = Workspace::create([
        'enterprise_id' => $enterprise->id,
        'owner_user_id' => $owner->id,
        'name'          => 'Test Workspace',
        'status'        => 'active',
        'path'          => '',
    ]);
    $ws->path = (string) $ws->id;
    $ws->save();

    // Seed roles
    $globalRoles = WorkspaceRole::whereNull('workspace_id')
        ->with('permissions:id')
        ->get();

    $now = now();
    WorkspaceRole::insert(
        $globalRoles->map(fn ($r) => [
            'workspace_id' => $ws->id,
            'name'         => $r->name,
            'is_base'      => true,
            'created_at'   => $now,
        ])->all()
    );

    $localRoles = WorkspaceRole::where('workspace_id', $ws->id)->pluck('id', 'name');

    $permsData = [];
    foreach ($globalRoles as $role) {
        $localId = $localRoles[$role->name] ?? null;
        if (!$localId || $role->permissions->isEmpty()) {
            continue;
        }
        foreach ($role->permissions as $perm) {
            $permsData[] = ['role_id' => $localId, 'permission_id' => $perm->id];
        }
    }
    if (!empty($permsData)) {
        DB::table('workspace_role_permissions')->insert($permsData);
    }

    // Add owner as owner member
    WorkspaceMember::create([
        'workspace_id' => $ws->id,
        'user_id'      => $owner->id,
        'role_id'      => $localRoles['owner'],
    ]);

    return $ws->fresh();
}

function addWsMember(Workspace $workspace, User $user, string $roleName): WorkspaceMember
{
    $role = WorkspaceRole::where('workspace_id', $workspace->id)->where('name', $roleName)->firstOrFail();
    return WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id'      => $user->id,
        'role_id'      => $role->id,
    ]);
}

// ── List workspaces ───────────────────────────────────────────────────────────

it('lists root workspaces the user is a member of', function () {
    [$owner, $enterprise, $token] = wsCtx();

    $ws = createWs($enterprise, $owner);

    $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonPath('data.0.name', $ws->name);
})->group('pgsql');

it('does not list workspaces the user is not a member of', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $other = User::factory()->create();

    $ws = createWs($enterprise, $other);

    $result = $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson('/api/v1/workspaces')
        ->assertOk();

    expect($result->json('data'))->toBeEmpty();
})->group('pgsql');

// ── Show workspace ────────────────────────────────────────────────────────────

it('shows a workspace to a member', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson("/api/v1/workspaces/{$ws->getHashId()}")
        ->assertOk()
        ->assertJsonPath('data.name', $ws->name);
})->group('pgsql');

it('returns 403 when user is not a workspace member on show', function () {
    [$owner, $enterprise, $ownerToken] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $outsider = User::factory()->create();
    addToEnt($outsider, $enterprise);
    $outsiderToken = JWTAuth::fromUser($outsider);

    $this->withHeaders(wsHdr($outsiderToken, $enterprise))
        ->getJson("/api/v1/workspaces/{$ws->getHashId()}")
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::WorkspaceForbidden->value);
})->group('pgsql');

it('returns 404 for unknown workspace id on show', function () {
    [, $enterprise, $token] = wsCtx();

    $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson('/api/v1/workspaces/nonexistent')
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::WorkspaceNotFound->value);
})->group('pgsql');

// ── Update workspace ──────────────────────────────────────────────────────────

it('updates workspace name with workspace.edit permission', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $this->withHeaders(wsHdr($token, $enterprise))
        ->patchJson("/api/v1/workspaces/{$ws->getHashId()}", ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');
})->group('pgsql');

it('returns 403 when viewer tries to update workspace', function () {
    [$owner, $enterprise, $ownerToken] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $viewer = User::factory()->create();
    addToEnt($viewer, $enterprise);
    addWsMember($ws, $viewer, 'viewer');
    $viewerToken = JWTAuth::fromUser($viewer);

    $this->withHeaders(wsHdr($viewerToken, $enterprise))
        ->patchJson("/api/v1/workspaces/{$ws->getHashId()}", ['name' => 'Hacked'])
        ->assertForbidden();
})->group('pgsql');

// ── Archive workspace ─────────────────────────────────────────────────────────

it('archives workspace and changes status to archived', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $this->withHeaders(wsHdr($token, $enterprise))
        ->patchJson("/api/v1/workspaces/{$ws->getHashId()}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
})->group('pgsql');

it('returns 403 when non-owner tries to archive', function () {
    [$owner, $enterprise, $ownerToken] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $editor = User::factory()->create();
    addToEnt($editor, $enterprise);
    addWsMember($ws, $editor, 'editor');
    $editorToken = JWTAuth::fromUser($editor);

    $this->withHeaders(wsHdr($editorToken, $enterprise))
        ->patchJson("/api/v1/workspaces/{$ws->getHashId()}/archive")
        ->assertForbidden();
})->group('pgsql');

// ── Delete workspace ──────────────────────────────────────────────────────────

it('soft deletes workspace and marks direct children as orphaned', function () {
    [$owner, $enterprise, $token] = wsCtx();

    $pro = \App\Models\Plan::where('name', 'Pro')->first();
    $enterprise->subscriptions()->latest()->first()->update(['plan_id' => $pro->id]);

    $parent = createWs($enterprise, $owner);

    $childRes = $this->withHeaders(wsHdr($token, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Child',
            'parent_workspace_id' => $parent->getHashId(),
        ])
        ->assertCreated();

    $childId = $childRes->json('data.id');
    $child   = Workspace::findByHashId($childId);

    $this->withHeaders(wsHdr($token, $enterprise))
        ->deleteJson("/api/v1/workspaces/{$parent->getHashId()}")
        ->assertNoContent();

    expect(Workspace::withTrashed()->find($parent->id)->deleted_at)->not->toBeNull();
    expect($child->fresh()->status)->toBe('orphaned');
})->group('pgsql');

it('returns 403 when editor tries to delete workspace', function () {
    [$owner, $enterprise, $ownerToken] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $editor = User::factory()->create();
    addToEnt($editor, $enterprise);
    addWsMember($ws, $editor, 'editor');
    $editorToken = JWTAuth::fromUser($editor);

    $this->withHeaders(wsHdr($editorToken, $enterprise))
        ->deleteJson("/api/v1/workspaces/{$ws->getHashId()}")
        ->assertForbidden();
})->group('pgsql');

// ── List children ─────────────────────────────────────────────────────────────

it('lists direct children of a workspace', function () {
    [$owner, $enterprise, $token] = wsCtx();

    $pro = \App\Models\Plan::where('name', 'Pro')->first();
    $enterprise->subscriptions()->latest()->first()->update(['plan_id' => $pro->id]);

    $parent = createWs($enterprise, $owner);

    $this->withHeaders(wsHdr($token, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Child A',
            'parent_workspace_id' => $parent->getHashId(),
        ])
        ->assertCreated();

    $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson("/api/v1/workspaces/{$parent->getHashId()}/children")
        ->assertOk()
        ->assertJsonCount(1, 'data');
})->group('pgsql');

// ── Capabilities ──────────────────────────────────────────────────────────────

it('returns capabilities for workspace owner', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $response = $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson("/api/v1/workspaces/{$ws->getHashId()}/capabilities")
        ->assertOk();

    expect($response->json('data'))->toContain('workspace.view', 'workspace.edit', 'workspace.delete');
})->group('pgsql');

it('returns limited capabilities for viewer', function () {
    [$owner, $enterprise, $ownerToken] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $viewer = User::factory()->create();
    addToEnt($viewer, $enterprise);
    addWsMember($ws, $viewer, 'viewer');
    $viewerToken = JWTAuth::fromUser($viewer);

    $response = $this->withHeaders(wsHdr($viewerToken, $enterprise))
        ->getJson("/api/v1/workspaces/{$ws->getHashId()}/capabilities")
        ->assertOk();

    expect($response->json('data'))->toContain('workspace.view');
    expect($response->json('data'))->not->toContain('workspace.delete');
})->group('pgsql');

it('returns 403 on capabilities for non-member', function () {
    [$owner, $enterprise, $ownerToken] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $outsider = User::factory()->create();
    addToEnt($outsider, $enterprise);
    $outsiderToken = JWTAuth::fromUser($outsider);

    $this->withHeaders(wsHdr($outsiderToken, $enterprise))
        ->getJson("/api/v1/workspaces/{$ws->getHashId()}/capabilities")
        ->assertForbidden();
})->group('pgsql');

// ── Root quota ────────────────────────────────────────────────────────────────

it('returns root quota with used count and plan limit', function () {
    [$owner, $enterprise, $token] = wsCtx();
    createWs($enterprise, $owner);

    $response = $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson('/api/v1/workspaces/root-quota')
        ->assertOk();

    expect($response->json('data.used'))->toBe(1);
    expect($response->json('data.limit'))->toBe(2); // Free plan limit
})->group('pgsql');

// ── Members ───────────────────────────────────────────────────────────────────

it('lists members of a workspace', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $viewer = User::factory()->create();
    addToEnt($viewer, $enterprise);
    addWsMember($ws, $viewer, 'viewer');

    $response = $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson("/api/v1/workspaces/{$ws->getHashId()}/members")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
})->group('pgsql');

it('removes a member from workspace', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $viewer = User::factory()->create();
    addToEnt($viewer, $enterprise);
    $viewerMember = addWsMember($ws, $viewer, 'viewer');

    $this->withHeaders(wsHdr($token, $enterprise))
        ->deleteJson("/api/v1/workspaces/{$ws->getHashId()}/members/{$viewerMember->getHashId()}")
        ->assertNoContent();

    expect(WorkspaceMember::where('workspace_id', $ws->id)->where('user_id', $viewer->id)->exists())->toBeFalse();
})->group('pgsql');

it('assigns a new role to a workspace member', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $editor = User::factory()->create();
    addToEnt($editor, $enterprise);
    $editorMember = addWsMember($ws, $editor, 'editor');

    $viewerRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'viewer')->first();

    $this->withHeaders(wsHdr($token, $enterprise))
        ->patchJson("/api/v1/workspaces/{$ws->getHashId()}/members/{$editorMember->getHashId()}/role", [
            'role_id' => $viewerRole->getHashId(),
        ])
        ->assertOk()
        ->assertJsonPath('data.role.name', 'viewer');
})->group('pgsql');

it('prevents assigning owner role via assign endpoint', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $editor = User::factory()->create();
    addToEnt($editor, $enterprise);
    $editorMember = addWsMember($ws, $editor, 'editor');

    $ownerRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'owner')->first();

    $this->withHeaders(wsHdr($token, $enterprise))
        ->patchJson("/api/v1/workspaces/{$ws->getHashId()}/members/{$editorMember->getHashId()}/role", [
            'role_id' => $ownerRole->getHashId(),
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::WorkspaceRoleBaseImmutable->value);
})->group('pgsql');

// ── Roles ─────────────────────────────────────────────────────────────────────

it('lists all workspace roles with permissions', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $response = $this->withHeaders(wsHdr($token, $enterprise))
        ->getJson("/api/v1/workspaces/{$ws->getHashId()}/roles")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(4);
    expect(collect($response->json('data'))->pluck('name')->all())
        ->toContain('owner', 'admin', 'editor', 'viewer');
})->group('pgsql');

it('creates a custom role with permissions', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $this->withHeaders(wsHdr($token, $enterprise))
        ->postJson("/api/v1/workspaces/{$ws->getHashId()}/roles", [
            'name'        => 'uploader',
            'permissions' => ['asset.upload', 'workspace.view'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'uploader')
        ->assertJsonPath('data.is_base', false);

    expect(WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'uploader')->exists())->toBeTrue();
})->group('pgsql');

it('returns 403 when viewer tries to create a role', function () {
    [$owner, $enterprise, $ownerToken] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $viewer = User::factory()->create();
    addToEnt($viewer, $enterprise);
    addWsMember($ws, $viewer, 'viewer');
    $viewerToken = JWTAuth::fromUser($viewer);

    $this->withHeaders(wsHdr($viewerToken, $enterprise))
        ->postJson("/api/v1/workspaces/{$ws->getHashId()}/roles", ['name' => 'hacked'])
        ->assertForbidden();
})->group('pgsql');

it('deletes a custom role with no members', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $custom = WorkspaceRole::create([
        'workspace_id' => $ws->id,
        'name'         => 'temp',
        'is_base'      => false,
    ]);

    $this->withHeaders(wsHdr($token, $enterprise))
        ->deleteJson("/api/v1/workspaces/{$ws->getHashId()}/roles/{$custom->getHashId()}")
        ->assertNoContent();

    expect(WorkspaceRole::where('id', $custom->id)->exists())->toBeFalse();
})->group('pgsql');

it('returns 422 when deleting a base role', function () {
    [$owner, $enterprise, $token] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $viewerRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'viewer')->first();

    $this->withHeaders(wsHdr($token, $enterprise))
        ->deleteJson("/api/v1/workspaces/{$ws->getHashId()}/roles/{$viewerRole->getHashId()}")
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::WorkspaceRoleBaseImmutable->value);
})->group('pgsql');

it('returns 422 when deleting a role that has members', function () {
    [$owner, $enterprise, $ownerToken] = wsCtx();
    $ws = createWs($enterprise, $owner);

    $custom = WorkspaceRole::create([
        'workspace_id' => $ws->id,
        'name'         => 'occupied',
        'is_base'      => false,
    ]);

    $user = User::factory()->create();
    addToEnt($user, $enterprise);
    WorkspaceMember::create([
        'workspace_id' => $ws->id,
        'user_id'      => $user->id,
        'role_id'      => $custom->id,
    ]);

    $this->withHeaders(wsHdr($ownerToken, $enterprise))
        ->deleteJson("/api/v1/workspaces/{$ws->getHashId()}/roles/{$custom->getHashId()}")
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::WorkspaceRoleHasMembers->value);
})->group('pgsql');
