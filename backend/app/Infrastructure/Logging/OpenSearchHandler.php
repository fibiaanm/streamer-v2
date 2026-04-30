<?php

namespace App\Infrastructure\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use OpenSearch\Client;
use Throwable;

class OpenSearchHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly Client $client,
        private readonly string $index,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        try {
            // context is serialized as a JSON string under 'data' so every log entry
            // has a flat, consistent shape in OpenSearch. extra (request_id, etc.)
            // comes from Monolog processors and goes at the document root.
            $normalized = $this->normalizeContext($record->context);

            $body = array_merge(
                $record->extra,
                [
                    '@timestamp' => $record->datetime->format(\DateTimeInterface::ATOM),
                    'service'    => 'laravel',
                    'level'      => strtolower($record->level->name),
                    'channel'    => $record->channel,
                    'message'    => $record->message,
                    'data'       => empty($normalized)
                        ? null
                        : json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            );

            $this->client->index([
                'index' => $this->index . '-' . date('Y.m.d'),
                'body'  => $body,
            ]);
        } catch (Throwable $e) {
            // OpenSearch down / rejected — never affect the app, but trace to stderr
            // so the stack channel's stderr handler still captures the original log.
            error_log('[opensearch-handler] indexing failed: ' . $e->getMessage());
        }
    }

    private function normalizeContext(array $context): array
    {
        $normalized = [];
        foreach ($context as $key => $value) {
            $normalized[(string) $key] = $this->normalizeValue($value);
        }
        return $normalized;
    }

    private function normalizeValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 5) {
            return '[max depth]';
        }

        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof Throwable) {
            return sprintf(
                '%s: %s in %s:%d',
                get_class($value),
                $value->getMessage(),
                $value->getFile(),
                $value->getLine(),
            );
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->normalizeValue($v, $depth + 1), $value);
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded !== false ? $encoded : '[unserializable]';
    }
}
