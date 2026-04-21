<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Caso feliz ────────────────────────────────────────────────────────────────

it('uploads an avatar and returns avatar_url', function () {
    Storage::fake('s3');

    $user = User::factory()->create();

    $this->withHeaders([
        'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        'Accept'        => 'application/json',
    ])->post('/api/v1/auth/profile/avatar', [
        'avatar' => UploadedFile::fake()->image('photo.jpg', 100, 100),
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['avatar_url']]);

    expect($user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
});

it('replaces an existing avatar', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('avatar');

    expect($user->getMedia('avatar'))->toHaveCount(1);

    $this->withHeaders([
        'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        'Accept'        => 'application/json',
    ])->post('/api/v1/auth/profile/avatar', [
        'avatar' => UploadedFile::fake()->image('new.jpg', 100, 100),
    ])->assertOk();

    expect($user->fresh()->getMedia('avatar'))->toHaveCount(1);
});

// ── Validación ────────────────────────────────────────────────────────────────

it('rejects non-image files', function () {
    $user = User::factory()->create();

    $this->withHeaders([
        'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        'Accept'        => 'application/json',
    ])->post('/api/v1/auth/profile/avatar', [
        'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ])->assertUnprocessable();
});

it('rejects files larger than 2MB', function () {
    $user = User::factory()->create();

    $this->withHeaders([
        'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        'Accept'        => 'application/json',
    ])->post('/api/v1/auth/profile/avatar', [
        'avatar' => UploadedFile::fake()->image('big.jpg')->size(3000),
    ])->assertUnprocessable();
});

it('rejects missing file', function () {
    $user = User::factory()->create();

    $this->withHeaders([
        'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        'Accept'        => 'application/json',
    ])->postJson('/api/v1/auth/profile/avatar', [])
        ->assertUnprocessable();
});

// ── Sin autenticación ─────────────────────────────────────────────────────────

it('returns 401 without token on avatar upload', function () {
    $this->postJson('/api/v1/auth/profile/avatar', [])
        ->assertUnauthorized();
});
