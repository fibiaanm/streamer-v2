<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Application\TokenService;
use App\Domain\Auth\AuthPayload;
use App\Domain\Auth\Exceptions\RefreshTokenInvalidException;
use App\Domain\Auth\Http\AuthCookies;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AppPageController extends Controller
{
    public function __invoke(Request $request, TokenService $tokenService): Response|RedirectResponse
    {
        if (!$token = $request->cookie('access_token')) {
            return $this->handleNoToken($request, $tokenService);
        }

        try {
            $payload = AuthPayload::from($token);
        } catch (TokenExpiredException) {
            return $this->tryRefresh($request, $tokenService);
        } catch (Throwable) {
            return $this->clearAndRedirect();
        }

        if ($payload->isGuest() && !$this->isGuestAllowed($request->path())) {
            return redirect('/login');
        }

        return Inertia::render('App');
    }

    private function isGuestAllowed(string $path): bool
    {
        return collect(config('auth.guest_paths', []))->contains(
            fn (string $pattern) => $path === ltrim($pattern, '/'),
        );
    }

    private function handleNoToken(Request $request, TokenService $tokenService): Response|RedirectResponse
    {
        // Si hay refresh_token, intentar restaurar la sesión del usuario aunque
        // el access_token cookie ya haya caducado en el browser.
        if ($refreshToken = $request->cookie('refresh_token')) {
            try {
                $tokens = $tokenService->refresh($refreshToken);

                return redirect('/' . $request->path())
                    ->withCookie(AuthCookies::access($tokens['access_token']))
                    ->withCookie(AuthCookies::refresh($tokens['refresh_token']));
            } catch (Throwable) {
                // refresh inválido — limpiar y continuar como guest/login
                foreach (AuthCookies::forget() as $cookie) {
                    cookie()->queue($cookie);
                }
            }
        }

        if (!$this->isGuestAllowed($request->path())) {
            return redirect('/login');
        }

        $tokens = $tokenService->issueGuestToken();

        cookie()->queue(AuthCookies::guestAccess($tokens['access_token']));

        return Inertia::render('App');
    }

    private function tryRefresh(Request $request, TokenService $tokenService): RedirectResponse
    {
        // Token expirado en cookie — si era un guest, renovar directamente.
        $expiredToken = $request->cookie('access_token', '');
        if ($expiredToken && TokenService::isGuestToken($expiredToken)) {
            $tokens = $tokenService->issueGuestToken();

            return redirect('/' . $request->path())
                ->withCookie(AuthCookies::guestAccess($tokens['access_token']));
        }

        try {
            $tokens = $tokenService->refresh($request->cookie('refresh_token', ''));

            return redirect('/app')
                ->withCookie(AuthCookies::access($tokens['access_token']))
                ->withCookie(AuthCookies::refresh($tokens['refresh_token']));

        } catch (RefreshTokenInvalidException) {
            return $this->clearAndRedirect();
        } catch (Throwable) {
            return $this->clearAndRedirect();
        }
    }

    private function clearAndRedirect(): RedirectResponse
    {
        $redirect = redirect('/login');

        foreach (AuthCookies::forget() as $cookie) {
            $redirect = $redirect->withCookie($cookie);
        }

        return $redirect;
    }
}
