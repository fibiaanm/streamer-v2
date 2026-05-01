<?php

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestId;
use App\Domain\Assistant\Http\Middleware\ResolvePersonalEnterprise;
use App\Http\Middleware\AuthenticateJWT;
use App\Http\Middleware\SetActiveEnterprise;
use App\Infrastructure\Reporting\Reporter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Las cookies JWT se setean desde rutas API (sin EncryptCookies) y se
        // leen desde rutas web — excluirlas del cifrado evita el mismatch.
        $middleware->encryptCookies(except: ['access_token', 'refresh_token']);

        // RequestId en todas las requests (primero en la cadena)
        $middleware->prepend(RequestId::class);

        // Inertia en el grupo web
        $middleware->web(append: [HandleInertiaRequests::class]);

        // Aliases para usar en rutas
        $middleware->alias([
            'auth.jwt'              => AuthenticateJWT::class,
            'enterprise'            => SetActiveEnterprise::class,
            'assistant.personal'    => ResolvePersonalEnterprise::class,
            'assistant.service'     => \App\Domain\Assistant\Http\Middleware\AssistantServiceAuth::class,
            'assistant.user_route'  => \App\Domain\Assistant\Http\Middleware\ResolveUserFromRoute::class,
            'admin'                 => \App\Http\Middleware\EnsureAdmin::class,
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

        // ModelNotFoundException — log it so 404s from internal routes are visible
        $exceptions->report(function (ModelNotFoundException $e): void {
            Reporter::report($e);
        })->stop();

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
