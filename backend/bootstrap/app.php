<?php

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\AuthenticateJWT;
use App\Http\Middleware\SetActiveEnterprise;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // RequestId en todas las requests (primero en la cadena)
        $middleware->prepend(RequestId::class);

        // Aliases para usar en rutas
        $middleware->alias([
            'auth.jwt'   => AuthenticateJWT::class,
            'enterprise' => SetActiveEnterprise::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // AppException — errores de negocio formateados
        $exceptions->render(function (AppException $e, Request $request): \Illuminate\Http\JsonResponse {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code'    => $e->getErrorCode()->value,
                        'context' => $e->getContext(),
                    ],
                ], $e->getHttpStatus());
            }
        });

        // ValidationException
        $exceptions->render(function (ValidationException $e, Request $request): \Illuminate\Http\JsonResponse {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code'    => ErrorCode::ValidationFailed->value,
                        'context' => ['fields' => $e->errors()],
                    ],
                ], 422);
            }
        });
    })->create();
