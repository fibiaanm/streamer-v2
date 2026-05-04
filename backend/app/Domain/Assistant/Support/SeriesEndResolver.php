<?php

namespace App\Domain\Assistant\Support;

use Carbon\Carbon;
use RRule\RRule;
use Throwable;

class SeriesEndResolver
{
    public static function resolve(string $rrule, Carbon $dtstart): ?Carbon
    {
        if (preg_match('/UNTIL=([^;]+)/i', $rrule, $m)) {
            try {
                return Carbon::parse(trim($m[1]));
            } catch (Throwable) {}
        }

        if (preg_match('/COUNT=/i', $rrule)) {
            try {
                $r   = new RRule($rrule, $dtstart->toDateTime());
                $all = $r->getOccurrences();
                if ($all) {
                    return Carbon::instance(end($all));
                }
            } catch (Throwable) {}
        }

        return null;
    }
}
