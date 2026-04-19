<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\AuthPayload;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LoginPageController extends Controller
{
    private const IMAGES = [
        '/images/login/studio@2000.webp',
    ];

    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($token = $request->cookie('access_token')) {
            try {
                $payload = AuthPayload::from($token);

                if ($payload->isGuest()) {
                    return $this->loginPage();
                }

                if (!User::where('id', $payload->subject())->exists()) {
                    return $this->loginPageForgettingCookies();
                }

                return redirect('/switch');
            } catch (Throwable) {
                return $this->loginPageForgettingCookies();
            }
        }

        return $this->loginPage();
    }

    private function loginPage(): Response
    {
        return Inertia::render('Auth/Login', [
            'imageUrl' => self::IMAGES[array_rand(self::IMAGES)],
        ]);
    }

    private function loginPageForgettingCookies(): Response
    {
        cookie()->queue(cookie()->forget('access_token'));
        cookie()->queue(cookie()->forget('refresh_token'));

        return $this->loginPage();
    }
}
