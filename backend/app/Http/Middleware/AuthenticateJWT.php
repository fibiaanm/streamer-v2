<?php

namespace App\Http\Middleware;

use App\Domain\Auth\AuthPayload;
use App\Domain\Auth\Exceptions\TokenExpiredException;
use App\Domain\Auth\Exceptions\UnauthorizedException;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException as JWTTokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthenticateJWT
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var AuthPayload|null $payload */
        $payload = null;

        try {
            $token = $request->bearerToken() ?? $request->cookie('access_token');

            if (!$token) {
                throw new UnauthorizedException();
            }

            $payload = AuthPayload::from($token);

            if ($payload->isGuest()) {
                $request->attributes->set('is_guest', true);
                return $next($request);
            }

            $user = User::findOrFail($payload->subject());

            auth()->setUser($user);

        } catch (JWTTokenExpiredException) {
            Log::warning('auth.token_expired');
            throw new TokenExpiredException();

        } catch (JWTException) {
            Log::warning('auth.token_invalid');
            throw new UnauthorizedException();

        } catch (ModelNotFoundException) {
            Log::warning('auth.user_not_found', ['sub' => $payload?->subject()]);
            throw new UnauthorizedException();
        }

        return $next($request);
    }
}
