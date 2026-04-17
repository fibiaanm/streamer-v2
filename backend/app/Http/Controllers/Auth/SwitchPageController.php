<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class SwitchPageController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (!$token = $request->cookie('access_token')) {
            Log::info('auth.switch_no_token');
            return redirect('/login');
        }

        try {
            JWTAuth::setToken($token)->getPayload();
            Log::info('auth.switch_valid_token');
        } catch (Throwable) {
            Log::info('auth.switch_invalid_token');
            return redirect('/login')
                ->withCookie(cookie()->forget('access_token'))
                ->withCookie(cookie()->forget('refresh_token'));
        }

        return Inertia::render('Auth/Switch');
    }
}
