<?php

namespace App\Domain\Assistant\Http\Controllers\Lists;

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RemoveFromListController extends Controller
{
    public function __invoke(Request $request, AssistantList $list, ListItem $item): JsonResponse
    {
        $user = $request->user();

        if ($item->list_id !== $list->id) {
            abort(404);
        }

        if ($list->user_id === $user->id) {
            $item->delete();
            return response()->json(null, 204);
        }

        $share = $list->shares()
            ->where('shared_with_user_id', $user->id)
            ->whereNotNull('accepted_at')
            ->first();

        if (! $share || $share->permission !== 'write') {
            abort(403);
        }

        if ($item->added_by_user_id !== $user->id) {
            abort(403);
        }

        $item->delete();

        return response()->json(null, 204);
    }
}
