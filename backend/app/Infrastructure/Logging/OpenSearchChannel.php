<?php

namespace App\Infrastructure\Logging;

use Illuminate\Support\Facades\Context;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use OpenSearch\ClientBuilder;

class OpenSearchChannel
{
    public function __invoke(array $config): Logger
    {
        $client = ClientBuilder::create()
            ->setHosts([$config['host']])
            ->build();

        $handler = new OpenSearchHandler(
            client: $client,
            index: $config['index'],
            level: Level::fromName(strtolower($config['level'])),
        );

        $logger = new Logger('opensearch', [$handler]);

        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(extra: array_merge($record->extra, Context::all()));
        });

        return $logger;
    }
}
