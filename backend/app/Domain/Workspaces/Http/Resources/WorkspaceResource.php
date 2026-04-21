<?php

namespace App\Domain\Workspaces\Http\Resources;

use App\Services\HashId;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->getHashId(),
            'name'       => $this->name,
            'status'     => $this->status,
            'path'       => $this->path,
            'parent_id'  => $this->parent_workspace_id
                ? HashId::encode($this->parent_workspace_id)
                : null,
            'owner'      => [
                'id'   => $this->owner->getHashId(),
                'name' => $this->owner->name,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
