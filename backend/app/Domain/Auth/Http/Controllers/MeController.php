<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Http\Resources\UserResource;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            if ($request->attributes->get('is_guest')) {
                return ResponseFormatter::success([
                    'id'         => null,
                    'name'       => null,
                    'email'      => null,
                    'enterprise' => [
                        'id'          => null,
                        'name'        => null,
                        'type'        => 'personal',
                        'role'        => 'guest',
                        'permissions' => config('auth.guest_permissions', []),
                        'products'    => config('auth.guest_limits', []),
                    ],
                ]);
            }

            return ResponseFormatter::success(new UserResource(auth()->user()));

        } catch (Throwable $e) {
            Log::error('auth.me_unexpected', [
                'exception' => $e,
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
