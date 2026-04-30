<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\Memory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpsertMemoryController
{
    public function __invoke(Request $request, string $userId, string $category): JsonResponse
    {
        $request->validate([
            'description' => 'required|string',
            'content'     => 'required|string',
        ]);

        $user = User::findByHashId($userId) ?? throw new NotFoundHttpException();

        Memory::updateOrCreate(
            ['user_id' => $user->id, 'category' => $category],
            [
                'description' => $request->input('description'),
                'content'     => $request->input('content'),
            ],
        );

        return response()->json(['data' => true]);
    }
}
