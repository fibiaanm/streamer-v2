<?php

namespace App\Domain\Workspaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->getHashId(),
            'user' => [
                'id'         => $this->user->getHashId(),
                'name'       => $this->user->name,
                'email'      => $this->user->email,
                'avatar_url' => $this->user->getAvatarUrls(),
            ],
            'role' => [
                'id'   => $this->role->getHashId(),
                'name' => $this->role->name,
            ],
        ];
    }
}
