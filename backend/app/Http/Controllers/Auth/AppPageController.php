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
            AuthPayload::from($token);
        } catch (TokenExpiredException) {
            return $this->tryRefresh($request, $tokenService);
        } catch (Throwable) {
            return $this->clearAndRedirect();
        }

        return Inertia::render('App');
    }

    private function handleNoToken(Request $request, TokenService $tokenService): Response|RedirectResponse
    {
        $path       = $request->path();
        $guestPaths = config('auth.guest_paths', []);

        $isGuestAllowed = collect($guestPaths)->contains(
            fn (string $pattern) => $path === ltrim($pattern, '/'),
        );

        if (!$isGuestAllowed) {
            return redirect('/login');
        }

        $tokens = $tokenService->issueGuestToken();

        cookie()->queue(AuthCookies::access($tokens['access_token']));

        return Inertia::render('App');
    }

    private function tryRefresh(Request $request, TokenService $tokenService): RedirectResponse
    {
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
