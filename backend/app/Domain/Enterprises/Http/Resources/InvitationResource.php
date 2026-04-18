<?php

namespace App\Domain\Enterprises\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->getHashId(),
            'email'      => $this->email,
            'status'     => $this->status,
            'expires_at' => $this->expires_at->toISOString(),
            'role'       => [
                'id'   => $this->enterpriseRole->getHashId(),
                'name' => $this->enterpriseRole->name,
            ],
            'invited_by' => [
                'id'   => $this->invitedBy->getHashId(),
                'name' => $this->invitedBy->name,
            ],
        ];
    }
}
