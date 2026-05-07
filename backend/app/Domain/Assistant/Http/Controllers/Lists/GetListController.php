<?php

namespace App\Domain\Assistant\Http\Controllers\Lists;

use App\Domain\Assistant\Http\Resources\AssistantListResource;
use App\Domain\Assistant\Models\AssistantList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GetListController extends Controller
{
    public function __invoke(Request $request, AssistantList $list): JsonResponse
    {
        $user = $request->user();

        if ($list->user_id === $user->id) {
            $list->is_shared_with_me = false;
            $list->my_permission     = 'write';
        } else {
            $share = $list->shares()
                ->where('shared_with_user_id', $user->id)
                ->whereNotNull('accepted_at')
                ->first();

            if (! $share) {
                abort(403);
            }

            $list->is_shared_with_me = true;
            $list->my_permission     = $share->permission;
        }

        $list->load(['items', 'user']);

        return response()->json(['data' => new AssistantListResource($list)]);
    }
}
