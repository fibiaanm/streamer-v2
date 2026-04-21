<?php

namespace App\Domain\Auth\Http\Controllers;

use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProfileController
{
    public function __invoke(): JsonResponse
    {
        try {
            $user = auth()->user();

            $enterprises = $user->enterpriseMembers()
                ->where('status', 'active')
                ->with('enterprise')
                ->get()
                ->map(fn ($member) => [
                    'id'   => $member->enterprise->getHashId(),
                    'name' => $member->enterprise->name,
                    'type' => $member->enterprise->type,
                ]);

            return ResponseFormatter::success([
                'id'          => $user->getHashId(),
                'name'        => $user->name,
                'email'       => $user->email,
                'avatar_url'  => $user->getAvatarUrls(),
                'enterprises' => $enterprises,
            ]);

        } catch (Throwable $e) {
            Log::error('auth.profile_unexpected', [
                'exception' => $e,
            ]);
            return ResponseFormatter::serverError();
        }
    }
}
