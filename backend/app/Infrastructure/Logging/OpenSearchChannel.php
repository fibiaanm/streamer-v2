<?php

namespace App\Infrastructure\Logging;

use Monolog\Level;
use Monolog\Logger;
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

        return new Logger('opensearch', [$handler]);
    }
}
