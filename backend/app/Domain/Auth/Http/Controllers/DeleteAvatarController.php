<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteAvatarController
{
    public function __invoke(): JsonResponse
    {
        try {
            $user = auth()->user();
            $user->clearMediaCollection('avatar');

            return ResponseFormatter::success(['avatar_url' => $user->getAvatarUrls()]);

        } catch (Throwable $e) {
            Log::error('auth.delete_avatar_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
