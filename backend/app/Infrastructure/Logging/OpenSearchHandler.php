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
                    'context'    => $record->context,
                    'extra'      => $record->extra,
                ],
            ]);
        } catch (Throwable) {
            // silent fail — OpenSearch down does not affect the application
        }
    }
}
