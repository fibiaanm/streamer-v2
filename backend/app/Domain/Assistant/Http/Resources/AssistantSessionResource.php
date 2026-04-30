<?php

namespace App\Domain\Assistant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistantSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->getHashId(),
            'title'           => $this->title ?? 'Untitled',
            'is_active'       => $this->is_active,
            'started_at'      => $this->started_at?->toISOString(),
            'last_message_at' => $this->last_message_at?->toISOString(),
        ];
    }
}
