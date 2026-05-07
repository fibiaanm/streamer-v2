<?php

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListItem;
use App\Domain\Assistant\Models\ListShare;

// --- AddToList ---

it('owner can add items and they append in order', function () {
    [$user, $enterprise, $token] = asstCtx();

    $list = AssistantList::factory()->create(['user_id' => $user->id]);
    ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id, 'position' => 0]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/lists/{$list->getHashId()}/items", [
            'items' => [['content' => 'Segundo'], ['content' => 'Tercero']],
        ])
        ->assertCreated()
        ->assertJsonCount(2, 'data');

    expect(ListItem::where('list_id', $list->id)->count())->toBe(3);
    expect(ListItem::where('list_id', $list->id)->max('position'))->toBe(2);
});

it('added_by_user_id is set to the authenticated user', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id]);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
        'permission'          => 'write',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/lists/{$list->getHashId()}/items", [
            'items' => [['content' => 'Añadido por compartido']],
        ])
        ->assertCreated();

    $item = ListItem::where('list_id', $list->id)->first();
    expect($item->added_by_user_id)->toBe($user->id);
});

it('user without write permission cannot add items', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id]);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
        'permission'          => 'read',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->postJson("/api/v1/assistant/lists/{$list->getHashId()}/items", [
            'items' => [['content' => 'Denegado']],
        ])
        ->assertForbidden();
});

// --- UpdateListItem ---

it('owner can update any item', function () {
    [$user, $enterprise, $token] = asstCtx();

    $list = AssistantList::factory()->create(['user_id' => $user->id]);
    $item = ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/lists/{$list->getHashId()}/items/{$item->getHashId()}", [
            'content' => 'Actualizado',
            'status'  => 'done',
        ])
        ->assertOk()
        ->assertJsonPath('data.content', 'Actualizado')
        ->assertJsonPath('data.status', 'done');
});

it('write-share user can update items', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id]);
    $item  = ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $owner->id]);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
        'permission'          => 'write',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/lists/{$list->getHashId()}/items/{$item->getHashId()}", [
            'status' => 'done',
        ])
        ->assertOk();
});

it('read-only share user cannot update items', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id]);
    $item  = ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $owner->id]);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
        'permission'          => 'read',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->patchJson("/api/v1/assistant/lists/{$list->getHashId()}/items/{$item->getHashId()}", [
            'status' => 'done',
        ])
        ->assertForbidden();
});

// --- RemoveFromList ---

it('owner can remove any item', function () {
    [$user, $enterprise, $token] = asstCtx();

    $other = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $user->id]);
    $item  = ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $other->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->deleteJson("/api/v1/assistant/lists/{$list->getHashId()}/items/{$item->getHashId()}")
        ->assertNoContent();

    expect(ListItem::find($item->id))->toBeNull();
});

it('write-share user can only remove items they added', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner     = \App\Models\User::factory()->create();
    $list      = AssistantList::factory()->create(['user_id' => $owner->id]);
    $ownItem   = ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id]);
    $otherItem = ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $owner->id]);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
        'permission'          => 'write',
    ]);

    // Can remove own item
    $this->withHeaders(asstHdr($token, $enterprise))
        ->deleteJson("/api/v1/assistant/lists/{$list->getHashId()}/items/{$ownItem->getHashId()}")
        ->assertNoContent();

    // Cannot remove item added by owner
    $this->withHeaders(asstHdr($token, $enterprise))
        ->deleteJson("/api/v1/assistant/lists/{$list->getHashId()}/items/{$otherItem->getHashId()}")
        ->assertForbidden();
});

// --- ClearCompletedItems ---

it('owner can clear completed items', function () {
    [$user, $enterprise, $token] = asstCtx();

    $list = AssistantList::factory()->create(['user_id' => $user->id]);
    ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id, 'status' => 'pending']);
    ListItem::factory()->done()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id]);
    ListItem::factory()->done()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->deleteJson("/api/v1/assistant/lists/{$list->getHashId()}/items/completed")
        ->assertNoContent();

    expect(ListItem::where('list_id', $list->id)->count())->toBe(1);
    expect(ListItem::where('list_id', $list->id)->where('status', 'done')->count())->toBe(0);
});

it('shared user cannot clear completed items', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id]);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
        'permission'          => 'write',
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->deleteJson("/api/v1/assistant/lists/{$list->getHashId()}/items/completed")
        ->assertForbidden();
});
