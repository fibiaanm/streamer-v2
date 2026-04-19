<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\AuthPayload;
use App\Domain\Auth\Http\AuthCookies;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SwitchPageController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (!$token = $request->cookie('access_token')) {
            Log::info('auth.switch_no_token');
            return redirect('/login');
        }

        try {
            $payload = AuthPayload::from($token);

            if ($payload->isGuest()) {
                Log::info('auth.switch_guest_token');
                return redirect('/login');
            }

            if (!User::where('id', $payload->subject())->exists()) {
                Log::warning('auth.switch_user_not_found');
                return $this->forgetAndRedirect();
            }

            Log::info('auth.switch_valid_token');
        } catch (Throwable) {
            Log::info('auth.switch_invalid_token');
            return redirect('/login')
                ->withCookie(cookie()->forget('access_token'))
                ->withCookie(cookie()->forget('refresh_token'));
        }

        return Inertia::render('Auth/Switch');
    }

    private function forgetAndRedirect(): RedirectResponse
    {
        $redirect = redirect('/login');
        foreach (AuthCookies::forget() as $cookie) {
            $redirect = $redirect->withCookie($cookie);
        }
        return $redirect;
    }
}
