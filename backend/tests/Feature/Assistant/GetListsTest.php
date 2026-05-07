<?php

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListItem;
use App\Domain\Assistant\Models\ListShare;

it('returns own lists with item counts', function () {
    [$user, $enterprise, $token] = asstCtx();

    $list = AssistantList::factory()->create(['user_id' => $user->id, 'name' => 'Compras']);
    ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id, 'status' => 'pending']);
    ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id, 'status' => 'pending']);
    ListItem::factory()->done()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/lists')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Compras')
        ->assertJsonPath('data.0.items_count.pending', 2)
        ->assertJsonPath('data.0.items_count.done', 1);

    expect($response->json('data.0'))->not->toHaveKey('items');
});

it('does not return lists from other users', function () {
    [$user, $enterprise, $token] = asstCtx();

    $other = \App\Models\User::factory()->create();
    AssistantList::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/lists')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('does not include shared lists without include_shared flag', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id]);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/lists')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('includes accepted shared lists with include_shared=true', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id, 'name' => 'Lista compartida']);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
        'permission'          => 'write',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/lists?include_shared=true')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Lista compartida')
        ->assertJsonPath('data.0.is_shared_with_me', true)
        ->assertJsonPath('data.0.my_permission', 'write');
});

it('does not include pending (non-accepted) shared lists', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id]);
    ListShare::factory()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
        'accepted_at'         => null,
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/lists?include_shared=true')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
