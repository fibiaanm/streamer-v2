<?php

namespace App\Domain\Enterprises\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->getHashId(),
            'name'        => $this->name,
            'is_global'   => $this->isGlobal(),
            'permissions' => $this->permissions->pluck('name')->values()->all(),
        ];
    }
}
