<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $meta = is_string($this->resource->metadata_json)
            ? json_decode($this->resource->metadata_json, true)
            : ($this->resource->metadata_json ?? []);

        $cost = $meta['cost'] ?? [];

        return [
            'id'            => $this->resource->id,
            'title'         => $this->resource->title,
            'user_id'       => $this->resource->user_id,
            'user_name'     => $this->resource->user_name,
            'user_email'    => $this->resource->user_email,
            'message_count' => (int) ($meta['message_count'] ?? 0),
            'cost'          => [
                'input'  => (int) ($cost['input']  ?? 0),
                'output' => (int) ($cost['output'] ?? 0),
                'total'  => (int) ($cost['total']  ?? 0),
            ],
            'created_at'    => $this->resource->created_at,
        ];
    }
}
