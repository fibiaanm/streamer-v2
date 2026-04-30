<?php

namespace App\Infrastructure\Logging;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin logging wrapper that enforces the project's log shape:
 *   AppLog::error('event.name', ['key' => $value, 'exception' => $e])
 *
 * The $data array is serialized as a JSON string in the 'data' field so that
 * every log entry in OpenSearch has a flat, consistent structure:
 *   { message, data, level, service, channel, request_id, @timestamp }
 */
final class AppLog
{
    public static function info(string $message, array $data = []): void
    {
        Log::info($message, self::prepare($data));
    }

    public static function warning(string $message, array $data = []): void
    {
        Log::warning($message, self::prepare($data));
    }

    public static function error(string $message, array $data = []): void
    {
        Log::error($message, self::prepare($data));
    }

    public static function debug(string $message, array $data = []): void
    {
        Log::debug($message, self::prepare($data));
    }

    private static function prepare(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        return ['data' => json_encode(self::normalize($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    private static function normalize(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[(string) $key] = self::normalizeValue($value);
        }
        return $result;
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            // No stack traces — large strings cause OpenSearch to silently reject documents
            return sprintf('%s: %s in %s:%d', get_class($value), $value->getMessage(), $value->getFile(), $value->getLine());
        }

        if (is_array($value)) {
            return self::normalize($value);
        }

        return $value;
    }
}
