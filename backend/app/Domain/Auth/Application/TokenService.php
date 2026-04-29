<?php

namespace App\Domain\Auth\Application;

use App\Domain\Auth\Exceptions\RefreshTokenInvalidException;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class TokenService
{
    public static function isGuestToken(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $decoded = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return is_array($decoded) && ($decoded['guest'] ?? false) === true;
    }

    public function issueGuestToken(): array
    {
        $ttl = 60; // minutos

        $payload = JWTFactory::customClaims([
            'sub'   => 'guest',
            'guest' => true,
            'exp'   => now()->addMinutes($ttl)->timestamp,
        ])->make();

        $token = JWTAuth::manager()->encode($payload)->get();

        return [
            'access_token' => $token,
            'expires_in'   => $ttl * 60,
        ];
    }

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
