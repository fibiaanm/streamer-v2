<?php

namespace App\Domain\Enterprises\Listeners;

use App\Domain\Enterprises\Events\EnterpriseUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PublishEnterpriseUpdated
{
    public function handle(EnterpriseUpdated $event): void
    {
        try {
            $enterprise = $event->enterprise;

            Redis::connection('pubsub')->publish("enterprise.{$enterprise->getHashId()}", json_encode([
                'event' => 'enterprise.updated',
                'data'  => [
                    'id'   => $enterprise->getHashId(),
                    'name' => $enterprise->name,
                ],
            ]));
        } catch (Throwable $e) {
            Log::error('enterprises.publish_updated_failed', ['exception' => $e]);
        }
    }
}
