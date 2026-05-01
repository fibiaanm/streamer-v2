<?php

namespace App\Domain\Assistant\Support;

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\Expense;
use InvalidArgumentException;

final class MorphTypeMap
{
    public const MAP = [
        'list'    => AssistantList::class,
        'expense' => Expense::class,
    ];

    public static function toClass(string $alias): string
    {
        return self::MAP[$alias] ?? throw new InvalidArgumentException("Unknown referenceable type: {$alias}");
    }

    public static function toAlias(string $class): string
    {
        return array_flip(self::MAP)[$class] ?? $class;
    }
}
