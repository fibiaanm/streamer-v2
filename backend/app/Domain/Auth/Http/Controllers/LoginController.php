<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Application\UseCases\LoginUseCase;
use App\Domain\Auth\Exceptions\InvalidCredentialsException;
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

            return ResponseFormatter::success($tokens);

        } catch (InvalidCredentialsException $e) {
            Log::warning('auth.login_failed', ['email' => $request->email]);
            return ResponseFormatter::error($e);

        } catch (Throwable $e) {
            Log::error('auth.login_unexpected', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
