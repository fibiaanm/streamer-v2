<?php

namespace App\Domain\Assistant\Support;

use App\Domain\Assistant\Models\AssistantEvent;
use App\Services\HashId;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class EventResolver
{
    public static function resolve(string $id, int $userId): ResolvedEvent
    {
        if (str_starts_with($id, 'v_')) {
            return self::resolveVirtual($id, $userId);
        }

        // Accept both raw integer IDs (tests) and hash IDs (frontend)
        $decodedId = HashId::decode($id);
        $numericId = $decodedId ?? (is_numeric($id) ? (int) $id : null);

        if ($numericId === null) {
            throw new NotFoundHttpException("Event not found: {$id}");
        }

        $event = AssistantEvent::where('id', $numericId)
            ->where('user_id', $userId)
            ->firstOrFail();

        return ResolvedEvent::real($event);
    }

    private static function resolveVirtual(string $id, int $userId): ResolvedEvent
    {
        // Format: v_{series_id}_{YYYY-MM-DD}
        $parts = explode('_', $id, 3);

        if (count($parts) !== 3) {
            throw new NotFoundHttpException("Invalid virtual event ID: {$id}");
        }

        $seriesId     = (int) $parts[1];
        $occurrenceAt = Carbon::parse($parts[2]);

        $master = AssistantEvent::where('id', $seriesId)
            ->where('user_id', $userId)
            ->whereNull('series_id')
            ->whereNotNull('recurrence_rule')
            ->first();

        if (! $master) {
            throw new NotFoundHttpException("Master series not found for virtual event: {$id}");
        }

        return ResolvedEvent::virtual($seriesId, $occurrenceAt, $master);
    }
}
