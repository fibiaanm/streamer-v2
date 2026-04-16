<?php

use App\Exceptions\ErrorCode;
use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\RefreshToken;
use App\Models\Subscription;
use App\Models\User;

// ─── Happy path ───────────────────────────────────────────────────────────────

it('returns 201 with tokens on valid registration', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in']]);
});

it('persists the user in the database', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    expect(User::where('email', 'john@example.com')->exists())->toBeTrue();
});

it('creates a personal enterprise owned by the new user', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user = User::where('email', 'john@example.com')->firstOrFail();

    expect(
        Enterprise::where('owner_id', $user->id)
            ->where('type', 'personal')
            ->exists()
    )->toBeTrue();
});

it('creates an active owner membership for the new user', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user = User::where('email', 'john@example.com')->firstOrFail();

    $member = EnterpriseMember::where('user_id', $user->id)->firstOrFail();

    expect($member->status)->toBe('active');
    expect($member->role->name)->toBe('owner');
    expect($member->role->enterprise_id)->toBeNull(); // rol global
});

it('creates an active Free subscription for the new enterprise', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user       = User::where('email', 'john@example.com')->firstOrFail();
    $enterprise = Enterprise::where('owner_id', $user->id)->firstOrFail();

    $subscription = Subscription::where('enterprise_id', $enterprise->id)->firstOrFail();

    expect($subscription->status)->toBe('active');
    expect($subscription->plan->name)->toBe('Free');
});

it('stores a refresh token in the database', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $user = User::where('email', 'john@example.com')->firstOrFail();

    expect(RefreshToken::where('user_id', $user->id)->exists())->toBeTrue();
});

// ─── Validation errors ────────────────────────────────────────────────────────

it('returns 422 on empty body', function () {
    $this->postJson('/api/v1/auth/register', [])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when name is missing', function () {
    $this->postJson('/api/v1/auth/register', [
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when name exceeds 255 characters', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => str_repeat('a', 256),
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when email is missing', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when email format is invalid', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'not-an-email',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when email is already taken', function () {
    User::factory()->create(['email' => 'john@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when password is missing', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when password is shorter than 8 characters', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'short',
        'password_confirmation' => 'short',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when password_confirmation is missing', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'     => 'John Doe',
        'email'    => 'john@example.com',
        'password' => 'secret123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});

it('returns 422 when password and confirmation do not match', function () {
    $this->postJson('/api/v1/auth/register', [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'different456',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', ErrorCode::ValidationFailed->value);
});
