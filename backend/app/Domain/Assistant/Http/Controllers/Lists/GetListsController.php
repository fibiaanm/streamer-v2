<?php

namespace App\Domain\Assistant\Http\Controllers\Lists;

use App\Domain\Assistant\Http\Resources\AssistantListResource;
use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GetListsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $ownLists = AssistantList::where('user_id', $user->id)
            ->withCount([
                'items as pending_items_count' => fn ($q) => $q->where('status', 'pending'),
                'items as done_items_count'    => fn ($q) => $q->where('status', 'done'),
            ])
            ->with('user')
            ->get()
            ->each(function ($list) {
                $list->is_shared_with_me = false;
                $list->my_permission     = 'write';
            });

        $lists = $ownLists;

        if ($request->boolean('include_shared')) {
            $sharedLists = AssistantList::whereHas('shares', fn ($q) => $q
                ->where('shared_with_user_id', $user->id)
                ->whereNotNull('accepted_at')
            )
                ->withCount([
                    'items as pending_items_count' => fn ($q) => $q->where('status', 'pending'),
                    'items as done_items_count'    => fn ($q) => $q->where('status', 'done'),
                ])
                ->with([
                    'user',
                    'shares' => fn ($q) => $q->where('shared_with_user_id', $user->id),
                ])
                ->get()
                ->each(function ($list) use ($user) {
                    $share                       = $list->shares->first();
                    $list->is_shared_with_me     = true;
                    $list->my_permission         = $share?->permission ?? 'read';
                });

            $lists = $lists->concat($sharedLists);
        }

        return response()->json(['data' => AssistantListResource::collection($lists)]);
    }
}
