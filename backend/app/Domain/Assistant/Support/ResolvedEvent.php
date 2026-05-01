<?php

namespace App\Domain\Assistant\Support;

use App\Domain\Assistant\Models\AssistantEvent;
use Carbon\Carbon;

final class ResolvedEvent
{
    private function __construct(
        private readonly bool $virtual,
        private readonly ?AssistantEvent $model,
        private readonly ?int $seriesId,
        private readonly ?Carbon $occurrenceAt,
        private readonly ?AssistantEvent $masterModel,
    ) {}

    public static function real(AssistantEvent $event): self
    {
        return new self(
            virtual: false,
            model: $event,
            seriesId: null,
            occurrenceAt: null,
            masterModel: null,
        );
    }

    public static function virtual(int $seriesId, Carbon $occurrenceAt, AssistantEvent $master): self
    {
        return new self(
            virtual: true,
            model: null,
            seriesId: $seriesId,
            occurrenceAt: $occurrenceAt,
            masterModel: $master,
        );
    }

    public function isVirtual(): bool
    {
        return $this->virtual;
    }

    public function model(): AssistantEvent
    {
        return $this->model ?? throw new \LogicException('Virtual events have no model.');
    }

    public function seriesId(): int
    {
        return $this->seriesId ?? throw new \LogicException('Real events have no seriesId on ResolvedEvent.');
    }

    public function occurrenceAt(): Carbon
    {
        return $this->occurrenceAt ?? throw new \LogicException('Real events have no occurrenceAt on ResolvedEvent.');
    }

    public function master(): AssistantEvent
    {
        return $this->masterModel ?? throw new \LogicException('Real events have no master on ResolvedEvent.');
    }

    public function materialize(array $overrides = []): AssistantEvent
    {
        if (! $this->virtual) {
            throw new \LogicException('Cannot materialize a real event.');
        }

        return AssistantEvent::create(array_merge([
            'user_id'       => $this->masterModel->user_id,
            'series_id'     => $this->seriesId,
            'occurrence_at' => $this->occurrenceAt,
            'event_at'      => $this->occurrenceAt,
            'content'       => $this->masterModel->content,
            'type'          => $this->masterModel->type,
            'status'        => 'active',
        ], $overrides));
    }
}
