<?php

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListItem;
use App\Domain\Assistant\Models\ListShare;

it('returns list with items ordered by position', function () {
    [$user, $enterprise, $token] = asstCtx();

    $list = AssistantList::factory()->create(['user_id' => $user->id]);
    ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id, 'content' => 'B', 'position' => 1]);
    ListItem::factory()->create(['list_id' => $list->id, 'added_by_user_id' => $user->id, 'content' => 'A', 'position' => 0]);

    $response = $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson("/api/v1/assistant/lists/{$list->getHashId()}")
        ->assertOk();

    $items = $response->json('data.items');
    expect($items)->toHaveCount(2);
    expect($items[0]['content'])->toBe('A');
    expect($items[1]['content'])->toBe('B');
});

it('returns 403 for a list that belongs to another user', function () {
    [$user, $enterprise, $token] = asstCtx();

    $other = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson("/api/v1/assistant/lists/{$list->getHashId()}")
        ->assertForbidden();
});

it('allows access to an accepted shared list', function () {
    [$user, $enterprise, $token] = asstCtx();

    $owner = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $owner->id]);
    ListShare::factory()->accepted()->create([
        'list_id'             => $list->id,
        'shared_with_user_id' => $user->id,
        'invited_by_user_id'  => $owner->id,
    ]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson("/api/v1/assistant/lists/{$list->getHashId()}")
        ->assertOk()
        ->assertJsonPath('data.is_shared_with_me', true);
});

it('returns 403 for a pending (not accepted) shared list', function () {
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
        ->getJson("/api/v1/assistant/lists/{$list->getHashId()}")
        ->assertForbidden();
});
