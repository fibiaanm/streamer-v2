<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Application\UseCases\RegisterUseCase;
use App\Domain\Auth\Http\AuthCookies;
use App\Domain\Auth\Http\Requests\RegisterRequest;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegisterController
{
    public function __invoke(RegisterRequest $request, RegisterUseCase $useCase): JsonResponse
    {
        try {
            $tokens = $useCase->execute($request->validated());

            Log::info('auth.registered', ['email' => $request->email]);

            return ResponseFormatter::created($tokens)
                ->withCookie(AuthCookies::access($tokens['access_token']))
                ->withCookie(AuthCookies::refresh($tokens['refresh_token']));

        } catch (Throwable $e) {
            Log::error('auth.register_unexpected', [
                'exception' => $e,
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
