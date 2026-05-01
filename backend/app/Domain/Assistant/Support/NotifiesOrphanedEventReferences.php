<?php

namespace App\Domain\Assistant\Support;

use App\Domain\Assistant\Jobs\NotifyOrphanedEventReferenceJob;
use Illuminate\Database\Eloquent\Model;

trait NotifiesOrphanedEventReferences
{
    public static function bootNotifiesOrphanedEventReferences(): void
    {
        static::deleting(function (Model $model) {
            $model->referencedEvents()->where('status', 'active')->each(
                fn ($event) => NotifyOrphanedEventReferenceJob::dispatch($event, $model, 'deleted')
            );
        });
    }
}
