<?php

namespace App\Infrastructure\Reporting;

use Illuminate\Support\Facades\Log;
use Throwable;

final class Reporter
{
    public static function report(Throwable $e, array $context = []): void
    {
        Log::error($e->getMessage(), array_merge([
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'code'      => $e->getCode(),
        ], $context));

        // TODO: forward to external observability (Sentry, Bugsnag, etc.)
        // static::notifyProvider($e, $context);
    }
}
