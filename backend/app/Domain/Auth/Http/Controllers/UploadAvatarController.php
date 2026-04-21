<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UploadAvatarController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        try {
            $user = auth()->user();
            $user->addMediaFromRequest('avatar')
                ->usingFileName('avatar.' . $request->file('avatar')->extension())
                ->toMediaCollection('avatar');

            return ResponseFormatter::success([
                'avatar_url' => $user->getAvatarUrls(),
            ]);

        } catch (Throwable $e) {
            Log::error('auth.upload_avatar_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
