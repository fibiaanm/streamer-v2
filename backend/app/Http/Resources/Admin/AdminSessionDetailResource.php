<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSessionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $meta = $this->resource->metadata_json ?? [];
        $cost = $meta['cost'] ?? ['input' => 0, 'output' => 0, 'total' => 0];

        return [
            'id'            => $this->resource->id,
            'title'         => $this->resource->title,
            'user_id'       => $this->resource->conversation->user_id,
            'user_name'     => $this->resource->conversation->user->name,
            'user_email'    => $this->resource->conversation->user->email,
            'message_count' => $meta['message_count'] ?? 0,
            'cost'          => [
                'input'  => (int) ($cost['input']  ?? 0),
                'output' => (int) ($cost['output'] ?? 0),
                'total'  => (int) ($cost['total']  ?? 0),
            ],
            'created_at'    => $this->resource->started_at,
            'messages'      => $this->resource->messages
                ->sortBy('created_at')
                ->values()
                ->map(fn ($m) => [
                    'id'               => $m->id,
                    'session_id'       => $m->session_id,
                    'role'             => $m->role,
                    'channel'          => $m->channel,
                    'content'          => $m->content,
                    'actions_json'     => $m->actions_json,
                    'metadata_json'    => $m->metadata_json,
                    'memory_processed' => $m->memory_processed,
                    'created_at'       => $m->created_at,
                ])
                ->all(),
        ];
    }
}
