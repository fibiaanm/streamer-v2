<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Domain\Auth\Http\Requests\UpdateProfileRequest;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateProfileController
{
    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $user->update(['name' => $request->name]);

            return ResponseFormatter::success([
                'id'         => $user->getHashId(),
                'name'       => $user->name,
                'email'      => $user->email,
                'avatar_url' => $user->getAvatarUrls(),
            ]);

        } catch (Throwable $e) {
            Log::error('auth.update_profile_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
