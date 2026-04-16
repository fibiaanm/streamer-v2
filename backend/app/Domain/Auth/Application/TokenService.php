<?php

namespace App\Domain\Auth\Application;

use App\Domain\Auth\Exceptions\RefreshTokenInvalidException;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class TokenService
{
    public function issueTokens(User $user): array
    {
        $accessToken = JWTAuth::fromUser($user);

        $rawRefresh  = Str::random(64);
        $expiresAt   = now()->addMinutes((int) config('jwt.refresh_ttl'));

        RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => hash('sha256', $rawRefresh),
            'expires_at' => $expiresAt,
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $rawRefresh,
            'expires_in'    => config('jwt.ttl') * 60,
        ];
    }

    public function refresh(string $rawToken): array
    {
        $hashed = hash('sha256', $rawToken);

        $record = RefreshToken::active()->where('token', $hashed)->first();

        if (!$record) {
            throw new RefreshTokenInvalidException();
        }

        $record->update(['revoked_at' => now()]);

        return $this->issueTokens($record->user);
    }

    public function revoke(string $rawToken): void
    {
        $hashed = hash('sha256', $rawToken);

        RefreshToken::where('token', $hashed)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAll(User $user): void
    {
        RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
