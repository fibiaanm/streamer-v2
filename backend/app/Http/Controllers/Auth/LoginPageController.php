<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginPageController extends Controller
{
    private const IMAGES = [
        '/images/login/studio@2000.webp',
    ];

    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($token = $request->cookie('access_token')) {
            try {
                JWTAuth::setToken($token)->getPayload();
                return redirect('/switch');
            } catch (Throwable) {
                return Inertia::render('Auth/Login', [
                    'imageUrl' => self::IMAGES[array_rand(self::IMAGES)],
                ])
                    ->withCookie(cookie()->forget('access_token'))
                    ->withCookie(cookie()->forget('refresh_token'));
            }
        }

        return Inertia::render('Auth/Login', [
            'imageUrl' => self::IMAGES[array_rand(self::IMAGES)],
        ]);
    }
}
