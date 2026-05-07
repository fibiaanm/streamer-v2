<?php

namespace App\Domain\Assistant\Http\Controllers\Lists;

use App\Domain\Assistant\Http\Resources\AssistantListResource;
use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\TypeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CreateListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'type'  => 'nullable|string|max:100',
            'items' => 'nullable|array',
            'items.*.content' => 'required|string',
        ]);

        $user = $request->user();

        if (! empty($validated['type'])) {
            TypeCatalog::firstOrCreate(
                ['user_id' => $user->id, 'domain' => 'list', 'name' => $validated['type']],
            );
        }

        $list = AssistantList::create([
            'user_id' => $user->id,
            'name'    => $validated['name'],
            'type'    => $validated['type'] ?? null,
        ]);

        foreach ($validated['items'] ?? [] as $index => $item) {
            $list->items()->create([
                'added_by_user_id' => $user->id,
                'content'          => $item['content'],
                'status'           => 'pending',
                'position'         => $index,
            ]);
        }

        $list->load(['items', 'user']);
        $list->is_shared_with_me = false;
        $list->my_permission     = 'write';

        return (new AssistantListResource($list))
            ->response()
            ->setStatusCode(201);
    }
}
