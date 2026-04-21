<?php

namespace App\Domain\Workspaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->getHashId(),
            'name'        => $this->name,
            'is_base'     => (bool) $this->is_base,
            'permissions' => $this->permissions->pluck('name')->values()->all(),
        ];
    }
}
