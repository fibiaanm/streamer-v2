<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

// ── Caso feliz ────────────────────────────────────────────────────────────────

it('deletes the avatar and returns avatar_url null', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $user->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection('avatar');

    expect($user->getFirstMedia('avatar'))->not->toBeNull();

    $this->withHeaders([
        'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        'Accept'        => 'application/json',
    ])->deleteJson('/api/v1/auth/profile/avatar')
        ->assertOk()
        ->assertJsonPath('data.avatar_url.jpeg', '')
        ->assertJsonPath('data.avatar_url.webp', '');

    expect($user->fresh()->getFirstMedia('avatar'))->toBeNull();
});

it('is idempotent when no avatar exists', function () {
    $user = User::factory()->create();

    $this->withHeaders([
        'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        'Accept'        => 'application/json',
    ])->deleteJson('/api/v1/auth/profile/avatar')
        ->assertOk()
        ->assertJsonPath('data.avatar_url.jpeg', '')
        ->assertJsonPath('data.avatar_url.webp', '');
});

// ── Sin autenticación ─────────────────────────────────────────────────────────

it('returns 401 without token on avatar delete', function () {
    $this->deleteJson('/api/v1/auth/profile/avatar')
        ->assertUnauthorized();
});
