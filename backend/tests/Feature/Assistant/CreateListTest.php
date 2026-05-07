<?php

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListItem;
use App\Domain\Assistant\Models\TypeCatalog;

it('creates a list and returns 201', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/lists', ['name' => 'Supermercado', 'type' => 'shopping'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Supermercado')
        ->assertJsonPath('data.type', 'shopping')
        ->assertJsonPath('data.is_shared_with_me', false)
        ->assertJsonPath('data.my_permission', 'write');

    expect(AssistantList::where('user_id', $user->id)->count())->toBe(1);
});

it('creates initial items with correct positions', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/lists', [
            'name'  => 'Compras',
            'items' => [
                ['content' => 'Leche'],
                ['content' => 'Pan'],
                ['content' => 'Huevos'],
            ],
        ])
        ->assertCreated()
        ->assertJsonCount(3, 'data.items');

    $items = ListItem::orderBy('position')->get();
    expect($items->pluck('content')->all())->toBe(['Leche', 'Pan', 'Huevos']);
    expect($items->pluck('position')->all())->toBe([0, 1, 2]);
});

it('auto-creates TypeCatalog when type does not exist', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/lists', ['name' => 'Mi lista', 'type' => 'custom_type'])
        ->assertCreated();

    expect(TypeCatalog::where('user_id', $user->id)->where('domain', 'list')->where('name', 'custom_type')->exists())->toBeTrue();
});

it('creates list without type', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/lists', ['name' => 'Sin tipo'])
        ->assertCreated()
        ->assertJsonPath('data.type', null);
});

it('requires a name', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson('/api/v1/assistant/lists', [])
        ->assertUnprocessable();
});
