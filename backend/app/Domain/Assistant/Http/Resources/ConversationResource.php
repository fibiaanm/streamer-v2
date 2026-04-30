<?php

namespace App\Domain\Assistant\Http\Resources;

use App\Domain\Assistant\Models\AssistantSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function __construct($resource, private readonly ?AssistantSession $activeSession)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->getHashId(),
            'active_session_id' => $this->activeSession?->getHashId(),
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
