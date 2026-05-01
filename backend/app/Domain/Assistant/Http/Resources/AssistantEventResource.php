<?php

namespace App\Domain\Assistant\Http\Resources;

use App\Domain\Assistant\Support\MorphTypeMap;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistantEventResource extends JsonResource
{
    private bool $isVirtual = false;

    public static function virtual(array $data): self
    {
        $instance            = new self((object) $data);
        $instance->isVirtual = true;
        return $instance;
    }

    public function toArray(Request $request): array
    {
        if ($this->isVirtual) {
            return [
                'id'        => $this->resource->id,
                'virtual'   => true,
                'series_id' => $this->resource->series_id,
                'content'   => $this->resource->content,
                'event_at'  => $this->resource->event_at,
                'reminders' => [],
            ];
        }

        return [
            'id'               => $this->resource->getHashId(),
            'virtual'          => false,
            'series_id'        => $this->resource->series_id,
            'content'          => $this->resource->content,
            'event_at'         => $this->resource->event_at?->toIso8601String(),
            'event_end'        => $this->resource->event_end?->toIso8601String(),
            'type'             => $this->resource->type,
            'recurrence_rule'  => $this->resource->recurrence_rule,
            'status'           => $this->resource->status,
            'reference'        => $this->resolveReference(),
            'reminders'        => $this->resolveReminders(),
        ];
    }

    private function resolveReference(): ?array
    {
        if (! $this->resource->referenceable_type || ! $this->resource->referenceable_id) {
            return null;
        }

        $related = $this->resource->referenceable;
        if (! $related) {
            return null;
        }

        return [
            'type'  => MorphTypeMap::toAlias($this->resource->referenceable_type),
            'id'    => method_exists($related, 'getHashId') ? $related->getHashId() : $related->id,
            'label' => method_exists($related, 'eventReferenceLabel') ? $related->eventReferenceLabel() : '',
        ];
    }

    private function resolveReminders(): array
    {
        return $this->resource->reminders->map(fn ($r) => [
            'id'      => $r->getHashId(),
            'fire_at' => $r->fire_at->toIso8601String(),
            'message' => $r->message,
            'status'  => $r->status,
        ])->all();
    }
}
