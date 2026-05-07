<?php

namespace App\Domain\Assistant\Http\Controllers\Lists;

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UpdateListItemController extends Controller
{
    public function __invoke(Request $request, AssistantList $list, ListItem $item): JsonResponse
    {
        $user = $request->user();

        $this->authorizeAccess($list, $user->id);

        if ($item->list_id !== $list->id) {
            abort(404);
        }

        $validated = $request->validate([
            'content'  => 'sometimes|string',
            'status'   => 'sometimes|in:pending,done',
            'position' => 'sometimes|integer|min:0',
        ]);

        $item->update($validated);

        return response()->json(['data' => [
            'id'       => $item->getHashId(),
            'content'  => $item->content,
            'status'   => $item->status,
            'position' => $item->position,
        ]]);
    }

    private function authorizeAccess(AssistantList $list, int $userId): void
    {
        if ($list->user_id === $userId) {
            return;
        }

        $share = $list->shares()
            ->where('shared_with_user_id', $userId)
            ->whereNotNull('accepted_at')
            ->first();

        if (! $share || $share->permission !== 'write') {
            abort(403);
        }
    }
}
