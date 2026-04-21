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

function createContext(): array
{
    $owner        = User::factory()->create();
    $member       = $owner->enterpriseMembers()->first();
    $enterprise   = $member->enterprise;
    $token        = JWTAuth::fromUser($owner);

    return [$owner, $enterprise, $token];
}

function wsHeaders(string $token, Enterprise $enterprise): array
{
    return [
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ];
}

function addToEnterprise(User $user, Enterprise $enterprise): void
{
    $memberRole = EnterpriseRole::whereNull('enterprise_id')
        ->where('name', 'member')
        ->firstOrFail();

    EnterpriseMember::create([
        'user_id'       => $user->id,
        'enterprise_id' => $enterprise->id,
        'role_id'       => $memberRole->id,
        'status'        => 'active',
    ]);
}

// ── Happy path ────────────────────────────────────────────────────────────────

it('creates a root workspace and returns 201', function () {
    [$owner, $enterprise, $token] = createContext();

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Marketing'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Marketing')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.parent_id', null);

    $this->assertDatabaseHas('workspaces', [
        'name'          => 'Marketing',
        'owner_user_id' => $owner->id,
        'enterprise_id' => $enterprise->id,
    ]);
})->group('pgsql');

it('seeds 4 base roles on workspace creation', function () {
    [, $enterprise, $token] = createContext();

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Design'])
        ->assertCreated();

    $workspace = Workspace::where('name', 'Design')->first();

    expect($workspace->roles()->count())->toBe(4);
    expect($workspace->roles()->orderBy('name')->pluck('name')->all())
        ->toEqual(['admin', 'editor', 'owner', 'viewer']);
})->group('pgsql');

it('adds creator as owner member on workspace creation', function () {
    [$owner, $enterprise, $token] = createContext();

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Product'])
        ->assertCreated();

    $workspace = Workspace::where('name', 'Product')->first();
    $member    = WorkspaceMember::where('workspace_id', $workspace->id)
        ->where('user_id', $owner->id)
        ->with('role')
        ->first();

    expect($member)->not->toBeNull();
    expect($member->role->name)->toBe('owner');
})->group('pgsql');

it('creates child workspace with correct ltree path', function () {
    [$owner, $enterprise, $token] = createContext();

    // Pro plan allows depth > 1
    $pro = \App\Models\Plan::where('name', 'Pro')->first();
    $enterprise->enterpriseProducts()->latest()->first()->update(['plan_id' => $pro->id]);

    $parentRes = $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Parent'])
        ->assertCreated();

    $parentId = $parentRes->json('data.id');

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Child',
            'parent_workspace_id' => $parentId,
        ])
        ->assertCreated()
        ->assertJsonPath('data.parent_id', $parentId);

    $parent = Workspace::where('name', 'Parent')->first();
    $child  = Workspace::where('name', 'Child')->first();

    expect($child->path)->toBe($parent->path . '.' . $child->id);
})->group('pgsql');

it('copies parent members to child preserving roles', function () {
    [$owner, $enterprise, $ownerToken] = createContext();

    $pro = \App\Models\Plan::where('name', 'Pro')->first();
    $enterprise->enterpriseProducts()->latest()->first()->update(['plan_id' => $pro->id]);

    $parentRes = $this->withHeaders(wsHeaders($ownerToken, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Parent'])
        ->assertCreated();

    $parentId = $parentRes->json('data.id');
    $parent   = Workspace::where('name', 'Parent')->first();

    // Add editor to parent (also needs enterprise membership)
    $editor     = User::factory()->create();
    addToEnterprise($editor, $enterprise);
    $editorRole = WorkspaceRole::where('workspace_id', $parent->id)->where('name', 'editor')->first();
    WorkspaceMember::create([
        'user_id'      => $editor->id,
        'workspace_id' => $parent->id,
        'role_id'      => $editorRole->id,
    ]);

    $this->withHeaders(wsHeaders($ownerToken, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Child',
            'parent_workspace_id' => $parentId,
        ])
        ->assertCreated();

    $child = Workspace::where('name', 'Child')->first();

    expect(WorkspaceMember::where('workspace_id', $child->id)->count())->toBe(2);

    $editorInChild = WorkspaceMember::where('workspace_id', $child->id)
        ->where('user_id', $editor->id)
        ->with('role')
        ->first();

    expect($editorInChild->role->name)->toBe('editor');
})->group('pgsql');

it('copies parent custom roles with permissions to child', function () {
    [$owner, $enterprise, $token] = createContext();

    $pro = \App\Models\Plan::where('name', 'Pro')->first();
    $enterprise->enterpriseProducts()->latest()->first()->update(['plan_id' => $pro->id]);

    $parentRes = $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Parent'])
        ->assertCreated();

    $parentId = $parentRes->json('data.id');
    $parent   = Workspace::where('name', 'Parent')->first();

    // Add a custom role with one permission
    $permission = WorkspacePermission::where('name', 'asset.upload')->first();
    $custom     = WorkspaceRole::create([
        'workspace_id' => $parent->id,
        'name'         => 'uploader',
        'is_base'      => false,
    ]);
    $custom->permissions()->attach($permission->id);

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Child',
            'parent_workspace_id' => $parentId,
        ])
        ->assertCreated();

    $child      = Workspace::where('name', 'Child')->first();
    $childRoles = $child->roles()->pluck('name');

    expect($child->roles()->count())->toBe(5);
    expect($childRoles)->toContain('uploader');

    $childCustom = $child->roles()->where('name', 'uploader')->with('permissions')->first();
    expect($childCustom->permissions->pluck('name'))->toContain('asset.upload');
})->group('pgsql');

// ── Límites del plan ──────────────────────────────────────────────────────────

it('returns 422 when root workspace limit is reached', function () {
    [, $enterprise, $token] = createContext();

    // Free plan: workspaces.max = 2
    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'WS 1'])
        ->assertCreated();

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'WS 2'])
        ->assertCreated();

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'WS 3'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::PlanLimitExceeded->value)
        ->assertJsonPath('error.context.limit', 'workspaces');
})->group('pgsql');

it('returns 422 when depth limit is exceeded', function () {
    [, $enterprise, $token] = createContext();

    // Free plan: workspace_depth.max = 1 → solo raíz
    $parentRes = $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Root'])
        ->assertCreated();

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Child',
            'parent_workspace_id' => $parentRes->json('data.id'),
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::WorkspaceDepthExceeded->value);
})->group('pgsql');

it('unlimited plan (-1) allows unlimited root workspaces', function () {
    [, $enterprise, $token] = createContext();

    $business = \App\Models\Plan::where('name', 'Business')->first();
    $enterprise->enterpriseProducts()->latest()->first()->update(['plan_id' => $business->id]);

    foreach (range(1, 5) as $i) {
        $this->withHeaders(wsHeaders($token, $enterprise))
            ->postJson('/api/v1/workspaces', ['name' => "WS $i"])
            ->assertCreated();
    }
})->group('pgsql');

// ── Autorización ──────────────────────────────────────────────────────────────

it('returns 403 when user lacks create_child permission in parent', function () {
    [$owner, $enterprise, $ownerToken] = createContext();

    $pro = \App\Models\Plan::where('name', 'Pro')->first();
    $enterprise->enterpriseProducts()->latest()->first()->update(['plan_id' => $pro->id]);

    $parentRes = $this->withHeaders(wsHeaders($ownerToken, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Parent'])
        ->assertCreated();

    $parentId = $parentRes->json('data.id');
    $parent   = Workspace::where('name', 'Parent')->first();

    // Viewer has no create_child permission
    $viewer     = User::factory()->create();
    addToEnterprise($viewer, $enterprise);
    $viewerRole = WorkspaceRole::where('workspace_id', $parent->id)->where('name', 'viewer')->first();
    WorkspaceMember::create([
        'user_id'      => $viewer->id,
        'workspace_id' => $parent->id,
        'role_id'      => $viewerRole->id,
    ]);
    $viewerToken = JWTAuth::fromUser($viewer);

    $this->withHeaders(wsHeaders($viewerToken, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Forbidden Child',
            'parent_workspace_id' => $parentId,
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::WorkspaceForbidden->value);
})->group('pgsql');

it('returns 403 when user is not a member of parent workspace', function () {
    [$owner, $enterprise, $ownerToken] = createContext();

    $pro = \App\Models\Plan::where('name', 'Pro')->first();
    $enterprise->enterpriseProducts()->latest()->first()->update(['plan_id' => $pro->id]);

    $parentRes = $this->withHeaders(wsHeaders($ownerToken, $enterprise))
        ->postJson('/api/v1/workspaces', ['name' => 'Parent'])
        ->assertCreated();

    $outsider = User::factory()->create();
    addToEnterprise($outsider, $enterprise);
    $outsiderToken = JWTAuth::fromUser($outsider);

    $this->withHeaders(wsHeaders($outsiderToken, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Child',
            'parent_workspace_id' => $parentRes->json('data.id'),
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::WorkspaceForbidden->value);
})->group('pgsql');

// ── Errores de input ──────────────────────────────────────────────────────────

it('returns 404 when parent workspace id does not resolve', function () {
    [, $enterprise, $token] = createContext();

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', [
            'name'                => 'Orphan',
            'parent_workspace_id' => 'nonexistent',
        ])
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::WorkspaceNotFound->value);
})->group('pgsql');

it('returns 401 when unauthenticated', function () {
    $this->postJson('/api/v1/workspaces', ['name' => 'Test'])
        ->assertUnauthorized();
})->group('pgsql');

it('returns 422 when name is missing', function () {
    [, $enterprise, $token] = createContext();

    $this->withHeaders(wsHeaders($token, $enterprise))
        ->postJson('/api/v1/workspaces', [])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
})->group('pgsql');
