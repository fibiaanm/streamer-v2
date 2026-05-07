<?php

namespace App\Domain\Assistant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistantListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'                => $this->getHashId(),
            'name'              => $this->name,
            'type'              => $this->type,
            'owner_id'          => $this->user->getHashId(),
            'is_shared_with_me' => (bool) ($this->is_shared_with_me ?? false),
            'my_permission'     => $this->my_permission ?? 'write',
            'items_count'       => $this->resolveItemCounts(),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];

        if ($this->relationLoaded('items')) {
            $data['items'] = $this->items->map(fn ($item) => [
                'id'       => $item->getHashId(),
                'content'  => $item->content,
                'status'   => $item->status,
                'position' => $item->position,
                'added_by' => $item->addedBy?->getHashId() ?? $item->added_by_user_id,
            ])->values()->all();
        }

        return $data;
    }

    private function resolveItemCounts(): array
    {
        if ($this->relationLoaded('items')) {
            return [
                'pending' => $this->items->where('status', 'pending')->count(),
                'done'    => $this->items->where('status', 'done')->count(),
            ];
        }

        return [
            'pending' => (int) ($this->pending_items_count ?? 0),
            'done'    => (int) ($this->done_items_count ?? 0),
        ];
    }
}
