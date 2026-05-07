<?php

namespace App\Domain\Assistant\Http\Controllers\Lists;

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AddToListController extends Controller
{
    public function __invoke(Request $request, AssistantList $list): JsonResponse
    {
        $user = $request->user();

        $this->authorizeAccess($list, $user->id, requireWrite: true);

        $validated = $request->validate([
            'items'           => 'required|array|min:1',
            'items.*.content' => 'required|string',
        ]);

        $nextPosition = $list->items()->max('position') ?? -1;

        $created = [];
        foreach ($validated['items'] as $item) {
            $nextPosition++;
            $created[] = $list->items()->create([
                'added_by_user_id' => $user->id,
                'content'          => $item['content'],
                'status'           => 'pending',
                'position'         => $nextPosition,
            ]);
        }

        $items = collect($created)->map(fn ($item) => [
            'id'       => $item->getHashId(),
            'content'  => $item->content,
            'status'   => $item->status,
            'position' => $item->position,
        ])->all();

        return response()->json(['data' => $items], 201);
    }

    private function authorizeAccess(AssistantList $list, int $userId, bool $requireWrite = false): void
    {
        if ($list->user_id === $userId) {
            return;
        }

        $share = $list->shares()
            ->where('shared_with_user_id', $userId)
            ->whereNotNull('accepted_at')
            ->first();

        if (! $share || ($requireWrite && $share->permission !== 'write')) {
            abort(403);
        }
    }
}
