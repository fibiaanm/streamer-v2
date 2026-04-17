<?php

namespace App\Domain\Auth\Http;

use Symfony\Component\HttpFoundation\Cookie;

class AuthCookies
{
    public static function access(string $token): Cookie
    {
        return cookie('access_token', $token, config('jwt.ttl'), '/', null, app()->isProduction(), true, false, 'lax');
    }

    public static function refresh(string $token): Cookie
    {
        return cookie('refresh_token', $token, config('jwt.refresh_ttl'), '/', null, app()->isProduction(), true, false, 'lax');
    }

    public static function forget(): array
    {
        return [
            cookie()->forget('access_token'),
            cookie()->forget('refresh_token'),
        ];
    }
}
