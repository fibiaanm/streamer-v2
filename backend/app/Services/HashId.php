<?php

namespace App\Services;

use Sqids\Sqids;

class HashId
{
    private static ?Sqids $sqids = null;

    private static function instance(): Sqids
    {
        return self::$sqids ??= new Sqids(
            minLength: 6,
        );
    }

    public static function encode(int $id): string
    {
        return self::instance()->encode([$id]);
    }

    public static function decode(string $hash): ?int
    {
        $ids = self::instance()->decode($hash);
        return $ids[0] ?? null;
    }
}
