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
            $this->client->index([
                'index' => $this->index . '-' . date('Y.m.d'),
                'body'  => [
                    '@timestamp' => $record->datetime->format(\DateTimeInterface::ATOM),
                    'service'    => 'laravel',
                    'level'      => strtolower($record->level->name),
                    'channel'    => $record->channel,
                    'message'    => $record->message,
                    'context'    => $this->normalizeContext($record->context),
                    'extra'      => $record->extra,
                ],
            ]);
        } catch (Throwable $e) {
            // OpenSearch down / rejected — never affect the app, but trace to stderr
            // so the stack channel's stderr handler still captures the original log.
            error_log('[opensearch-handler] indexing failed: ' . $e->getMessage());
        }
    }

    /**
     * Ensure context values are scalar or simple arrays so OpenSearch
     * doesn't reject the document due to dynamic mapping conflicts.
     */
    private function normalizeContext(array $context): array
    {
        $normalized = [];
        foreach ($context as $key => $value) {
            $normalized[(string) $key] = is_scalar($value) || is_null($value)
                ? $value
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $normalized;
    }
}
