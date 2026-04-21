<?php

use App\Domain\Enterprises\Exceptions\InvitationExpiredException;
use App\Exceptions\ErrorCode;
use App\Jobs\SendInvitationEmailJob;
use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceRole;
use Illuminate\Support\Facades\Queue;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Helpers (local scope to avoid collisions) ─────────────────────────────────

function invCtx(): array
{
    $owner      = User::factory()->create();
    $member     = $owner->enterpriseMembers()->first();
    $enterprise = $member->enterprise;
    $token      = JWTAuth::fromUser($owner);
    return [$owner, $enterprise, $token];
}

function invHdr(string $token, Enterprise $enterprise): array
{
    return [
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ];
}

function invAddToEnt(User $user, Enterprise $enterprise): void
{
    $r = EnterpriseRole::whereNull('enterprise_id')->where('name', 'member')->firstOrFail();
    EnterpriseMember::create([
        'user_id' => $user->id, 'enterprise_id' => $enterprise->id,
        'role_id' => $r->id, 'status' => 'active',
    ]);
}

function invMakeWs(Enterprise $enterprise, User $owner): Workspace
{
    $ws = Workspace::create([
        'enterprise_id' => $enterprise->id,
        'owner_user_id' => $owner->id,
        'name'          => 'Inv WS',
        'status'        => 'active',
        'path'          => '',
    ]);
    $ws->path = (string) $ws->id;
    $ws->save();

    $globalRoles = WorkspaceRole::whereNull('workspace_id')->with('permissions:id')->get();
    $now = now();
    WorkspaceRole::insert(
        $globalRoles->map(fn ($r) => [
            'workspace_id' => $ws->id,
            'name'         => $r->name,
            'is_base'      => true,
            'created_at'   => $now,
        ])->all()
    );
    $local = WorkspaceRole::where('workspace_id', $ws->id)->pluck('id', 'name');

    $perms = [];
    foreach ($globalRoles as $role) {
        $lid = $local[$role->name] ?? null;
        if (!$lid || $role->permissions->isEmpty()) continue;
        foreach ($role->permissions as $p) {
            $perms[] = ['role_id' => $lid, 'permission_id' => $p->id];
        }
    }
    if (!empty($perms)) {
        DB::table('workspace_role_permissions')->insert($perms);
    }

    WorkspaceMember::create([
        'workspace_id' => $ws->id,
        'user_id'      => $owner->id,
        'role_id'      => $local['owner'],
    ]);

    return $ws->fresh();
}

// ── InviteMemberController ─────────────────────────────────────────────────────

it('creates workspace invitation and dispatches email job', function () {
    Queue::fake();

    [$owner, $enterprise, $token] = invCtx();
    $ws = invMakeWs($enterprise, $owner);

    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    $this->withHeaders(invHdr($token, $enterprise))
        ->postJson("/api/v1/workspaces/{$ws->getHashId()}/invitations", [
            'email'   => 'newperson@example.com',
            'role_id' => $editorRole->getHashId(),
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'newperson@example.com')
        ->assertJsonPath('data.role.name', 'editor')
        ->assertJsonPath('data.status', 'pending');

    Queue::assertPushed(SendInvitationEmailJob::class);

    $this->assertDatabaseHas('invitations', [
        'email'             => 'newperson@example.com',
        'invitable_type'    => Workspace::class,
        'invitable_id'      => $ws->id,
        'workspace_role_id' => $editorRole->id,
        'status'            => 'pending',
    ]);
})->group('pgsql');

it('re-sends invitation refreshing token for previously expired one', function () {
    Queue::fake();

    [$owner, $enterprise, $token] = invCtx();
    $ws = invMakeWs($enterprise, $owner);

    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    // Create an expired invitation manually
    $old = Invitation::create([
        'invitable_type'     => Workspace::class,
        'invitable_id'       => $ws->id,
        'invited_by_user_id' => $owner->id,
        'email'              => 'retry@example.com',
        'workspace_role_id'  => $editorRole->id,
        'token'              => 'old-token',
        'status'             => 'expired',
        'expires_at'         => now()->subDays(1),
    ]);

    $this->withHeaders(invHdr($token, $enterprise))
        ->postJson("/api/v1/workspaces/{$ws->getHashId()}/invitations", [
            'email'   => 'retry@example.com',
            'role_id' => $editorRole->getHashId(),
        ])
        ->assertCreated();

    $refreshed = Invitation::find($old->id);
    expect($refreshed->status)->toBe('pending');
    expect($refreshed->token)->not->toBe('old-token');
    expect($refreshed->expires_at->isFuture())->toBeTrue();
})->group('pgsql');

it('returns 403 when viewer tries to invite member', function () {
    Queue::fake();

    [$owner, $enterprise, $ownerToken] = invCtx();
    $ws = invMakeWs($enterprise, $owner);

    $viewer = User::factory()->create();
    invAddToEnt($viewer, $enterprise);
    WorkspaceMember::create([
        'workspace_id' => $ws->id,
        'user_id'      => $viewer->id,
        'role_id'      => WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'viewer')->value('id'),
    ]);
    $viewerToken = JWTAuth::fromUser($viewer);

    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    $this->withHeaders(invHdr($viewerToken, $enterprise))
        ->postJson("/api/v1/workspaces/{$ws->getHashId()}/invitations", [
            'email'   => 'blocked@example.com',
            'role_id' => $editorRole->getHashId(),
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::WorkspaceForbidden->value);
})->group('pgsql');

it('returns 422 when trying to invite with owner role', function () {
    Queue::fake();

    [$owner, $enterprise, $token] = invCtx();
    $ws = invMakeWs($enterprise, $owner);

    $ownerRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'owner')->first();

    $this->withHeaders(invHdr($token, $enterprise))
        ->postJson("/api/v1/workspaces/{$ws->getHashId()}/invitations", [
            'email'   => 'someone@example.com',
            'role_id' => $ownerRole->getHashId(),
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::WorkspaceRoleBaseImmutable->value);
})->group('pgsql');

it('returns 422 when email is missing on invite', function () {
    Queue::fake();

    [$owner, $enterprise, $token] = invCtx();
    $ws = invMakeWs($enterprise, $owner);

    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    $this->withHeaders(invHdr($token, $enterprise))
        ->postJson("/api/v1/workspaces/{$ws->getHashId()}/invitations", [
            'role_id' => $editorRole->getHashId(),
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
})->group('pgsql');

// ── ShowWorkspaceInvitationController ─────────────────────────────────────────

it('shows workspace invitation details for valid token', function () {
    [$owner, $enterprise] = invCtx();
    $ws = invMakeWs($enterprise, $owner);

    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    $invitation = Invitation::create([
        'invitable_type'     => Workspace::class,
        'invitable_id'       => $ws->id,
        'invited_by_user_id' => $owner->id,
        'email'              => 'viewer@example.com',
        'workspace_role_id'  => $editorRole->id,
        'token'              => 'valid-show-token',
        'status'             => 'pending',
        'expires_at'         => now()->addDays(7),
    ]);

    $this->getJson('/api/v1/workspaces/invitations/valid-show-token')
        ->assertOk()
        ->assertJsonPath('data.email', 'viewer@example.com')
        ->assertJsonPath('data.workspace_name', $ws->name)
        ->assertJsonPath('data.role_name', 'editor')
        ->assertJsonPath('data.user_exists', false);
})->group('pgsql');

it('returns 404 for invalid workspace invitation token', function () {
    $this->getJson('/api/v1/workspaces/invitations/bad-token')
        ->assertNotFound()
        ->assertJsonPath('error.code', ErrorCode::EnterpriseInvitationInvalid->value);
})->group('pgsql');

it('returns 422 for expired workspace invitation', function () {
    [$owner, $enterprise] = invCtx();
    $ws = invMakeWs($enterprise, $owner);
    $role = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    Invitation::create([
        'invitable_type'     => Workspace::class,
        'invitable_id'       => $ws->id,
        'invited_by_user_id' => $owner->id,
        'email'              => 'late@example.com',
        'workspace_role_id'  => $role->id,
        'token'              => 'expired-ws-token',
        'status'             => 'pending',
        'expires_at'         => now()->subHour(),
    ]);

    $this->getJson('/api/v1/workspaces/invitations/expired-ws-token')
        ->assertStatus(422)
        ->assertJsonPath('error.code', ErrorCode::EnterpriseInvitationExpired->value);
})->group('pgsql');

// ── AcceptWorkspaceInvitationController ───────────────────────────────────────

it('existing user accepts workspace invitation and becomes member', function () {
    [$owner, $enterprise] = invCtx();
    $ws = invMakeWs($enterprise, $owner);
    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    $existing = User::factory()->create();
    invAddToEnt($existing, $enterprise);

    Invitation::create([
        'invitable_type'     => Workspace::class,
        'invitable_id'       => $ws->id,
        'invited_by_user_id' => $owner->id,
        'email'              => $existing->email,
        'workspace_role_id'  => $editorRole->id,
        'token'              => 'accept-existing-token',
        'status'             => 'pending',
        'expires_at'         => now()->addDays(7),
    ]);

    $this->postJson('/api/v1/workspaces/invitations/accept-existing-token/accept', [
        'password' => 'password',
    ])
    ->assertOk()
    ->assertJsonPath('data.workspace_id', $ws->getHashId());

    expect(WorkspaceMember::where('workspace_id', $ws->id)->where('user_id', $existing->id)->exists())
        ->toBeTrue();

    $accepted = Invitation::where('token', 'accept-existing-token')->first();
    expect($accepted->status)->toBe('accepted');
})->group('pgsql');

it('new user accepts workspace invitation, account is created and added to enterprise', function () {
    [$owner, $enterprise] = invCtx();
    $ws = invMakeWs($enterprise, $owner);
    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    Invitation::create([
        'invitable_type'     => Workspace::class,
        'invitable_id'       => $ws->id,
        'invited_by_user_id' => $owner->id,
        'email'              => 'brandnew@example.com',
        'workspace_role_id'  => $editorRole->id,
        'token'              => 'accept-newuser-token',
        'status'             => 'pending',
        'expires_at'         => now()->addDays(7),
    ]);

    $this->postJson('/api/v1/workspaces/invitations/accept-newuser-token/accept', [
        'name'     => 'Brand New',
        'password' => 'password123',
    ])
    ->assertOk();

    $newUser = User::where('email', 'brandnew@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('Brand New');

    // Added to enterprise
    expect(EnterpriseMember::where('enterprise_id', $enterprise->id)
        ->where('user_id', $newUser->id)->exists()
    )->toBeTrue();

    // Added to workspace
    expect(WorkspaceMember::where('workspace_id', $ws->id)
        ->where('user_id', $newUser->id)->exists()
    )->toBeTrue();
})->group('pgsql');

it('returns 422 when accepting with wrong password', function () {
    [$owner, $enterprise] = invCtx();
    $ws = invMakeWs($enterprise, $owner);
    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    $existing = User::factory()->create();
    invAddToEnt($existing, $enterprise);

    Invitation::create([
        'invitable_type'     => Workspace::class,
        'invitable_id'       => $ws->id,
        'invited_by_user_id' => $owner->id,
        'email'              => $existing->email,
        'workspace_role_id'  => $editorRole->id,
        'token'              => 'wrong-pass-token',
        'status'             => 'pending',
        'expires_at'         => now()->addDays(7),
    ]);

    $this->postJson('/api/v1/workspaces/invitations/wrong-pass-token/accept', [
        'password' => 'wrongpassword',
    ])
    ->assertUnauthorized()
    ->assertJsonPath('error.code', ErrorCode::AuthInvalidCredentials->value);
})->group('pgsql');

it('returns 422 when user is already a workspace member on accept', function () {
    [$owner, $enterprise] = invCtx();
    $ws = invMakeWs($enterprise, $owner);
    $editorRole = WorkspaceRole::where('workspace_id', $ws->id)->where('name', 'editor')->first();

    $existing = User::factory()->create();
    invAddToEnt($existing, $enterprise);
    WorkspaceMember::create([
        'workspace_id' => $ws->id,
        'user_id'      => $existing->id,
        'role_id'      => $editorRole->id,
    ]);

    Invitation::create([
        'invitable_type'     => Workspace::class,
        'invitable_id'       => $ws->id,
        'invited_by_user_id' => $owner->id,
        'email'              => $existing->email,
        'workspace_role_id'  => $editorRole->id,
        'token'              => 'already-member-token',
        'status'             => 'pending',
        'expires_at'         => now()->addDays(7),
    ]);

    $this->postJson('/api/v1/workspaces/invitations/already-member-token/accept', [
        'password' => 'password',
    ])
    ->assertStatus(422)
    ->assertJsonPath('error.code', ErrorCode::EnterpriseMemberExists->value);
})->group('pgsql');
