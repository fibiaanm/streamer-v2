<?php

namespace App\Domain\Assistant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistantMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->getHashId(),
            'role'       => $this->role,
            'content'    => $this->content,
            'channel'    => $this->channel,
            'actions'    => $this->actions_json ?? [],
            'metadata'   => $this->metadata_json ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
