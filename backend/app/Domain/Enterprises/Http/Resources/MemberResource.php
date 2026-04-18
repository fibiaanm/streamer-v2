<?php

namespace App\Domain\Enterprises\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->getHashId(),
            'status' => $this->status,
            'user'   => [
                'id'    => $this->user->getHashId(),
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
            'role'   => [
                'id'   => $this->role->getHashId(),
                'name' => $this->role->name,
            ],
        ];
    }
}
