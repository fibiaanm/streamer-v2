<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\Memory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetMemoriesController
{
    public function __invoke(Request $request, string $userId): JsonResponse
    {
        $user = User::findByHashId($userId) ?? throw new NotFoundHttpException();

        $memories = Memory::where('user_id', $user->id)
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->getHashId(),
                'category'    => $m->category,
                'description' => $m->description,
                'content'     => $m->content,
            ]);

        return response()->json(['data' => $memories]);
    }
}
