<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Application\UseCases\LoginUseCase;
use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use App\Domain\Auth\Http\AuthCookies;
use App\Domain\Auth\Http\Requests\LoginRequest;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoginController
{
    public function __invoke(LoginRequest $request, LoginUseCase $useCase): JsonResponse
    {
        try {
            $tokens = $useCase->execute($request->email, $request->password);

            Log::info('auth.login_success', ['email' => $request->email]);
            return ResponseFormatter::success($tokens)
                ->withCookie(AuthCookies::access($tokens['access_token']))
                ->withCookie(AuthCookies::refresh($tokens['refresh_token']));
        } catch (InvalidCredentialsException $e) {
            Log::warning('auth.login_failed', ['email' => $request->email]);
            return ResponseFormatter::error($e);

        } catch (Throwable $e) {
            Log::error('auth.login_unexpected', [
                'exception' => $e,
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
