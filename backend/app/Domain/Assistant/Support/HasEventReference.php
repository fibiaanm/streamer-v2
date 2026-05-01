<?php

namespace App\Domain\Assistant\Support;

use App\Domain\Assistant\Models\AssistantEvent;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasEventReference
{
    public function referencedEvents(): MorphMany
    {
        return $this->morphMany(AssistantEvent::class, 'referenceable');
    }

    abstract public function eventReferenceLabel(): string;
}
