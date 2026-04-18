<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Application\TokenService;
use App\Domain\Auth\Http\AuthCookies;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogoutController
{
    public function __invoke(Request $request, TokenService $tokenService): JsonResponse
    {
        try {
            $tokenService->revoke($request->cookie('refresh_token', ''));

            Log::info('auth.logout', ['user_id' => auth()->id()]);

            $response = ResponseFormatter::noContent();
            foreach (AuthCookies::forget() as $cookie) {
                $response->withCookie($cookie);
            }
            return $response;

        } catch (Throwable $e) {
            Log::error('auth.logout_unexpected', [
                'exception' => $e,
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
