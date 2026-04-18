<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Application\TokenService;
use App\Domain\Auth\Exceptions\RefreshTokenInvalidException;
use App\Domain\Auth\Http\AuthCookies;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshController
{
    public function __invoke(Request $request, TokenService $tokenService): JsonResponse
    {
        try {
            $tokens = $tokenService->refresh($request->cookie('refresh_token', ''));

            return ResponseFormatter::success($tokens)
                ->withCookie(AuthCookies::access($tokens['access_token']))
                ->withCookie(AuthCookies::refresh($tokens['refresh_token']));

        } catch (RefreshTokenInvalidException $e) {
            Log::warning('auth.refresh_invalid');
            return ResponseFormatter::error($e);

        } catch (Throwable $e) {
            Log::error('auth.refresh_unexpected', [
                'exception' => $e,
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
