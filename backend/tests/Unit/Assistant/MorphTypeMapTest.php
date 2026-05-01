<?php

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\Expense;
use App\Domain\Assistant\Support\MorphTypeMap;

it('resolves list alias to AssistantList class', function () {
    expect(MorphTypeMap::toClass('list'))->toBe(AssistantList::class);
});

it('resolves expense alias to Expense class', function () {
    expect(MorphTypeMap::toClass('expense'))->toBe(Expense::class);
});

it('throws InvalidArgumentException for unknown alias', function () {
    MorphTypeMap::toClass('unknown');
})->throws(\InvalidArgumentException::class, 'Unknown referenceable type: unknown');

it('resolves AssistantList class to list alias', function () {
    expect(MorphTypeMap::toAlias(AssistantList::class))->toBe('list');
});

it('resolves Expense class to expense alias', function () {
    expect(MorphTypeMap::toAlias(Expense::class))->toBe('expense');
});
