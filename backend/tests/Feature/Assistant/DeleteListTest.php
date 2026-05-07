<?php

use App\Domain\Assistant\Models\AssistantList;
use App\Domain\Assistant\Models\ListShare;

it('owner can delete their list', function () {
    [$user, $enterprise, $token] = asstCtx();

    $list = AssistantList::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->deleteJson("/api/v1/assistant/lists/{$list->getHashId()}")
        ->assertNoContent();

    expect(AssistantList::find($list->id))->toBeNull();
    expect(AssistantList::withTrashed()->find($list->id))->not->toBeNull();
});

it('non-owner cannot delete a list', function () {
    [$user, $enterprise, $token] = asstCtx();

    $other = \App\Models\User::factory()->create();
    $list  = AssistantList::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asstHdr($token, $enterprise))
        ->deleteJson("/api/v1/assistant/lists/{$list->getHashId()}")
        ->assertForbidden();
});

it('shared user cannot delete the list', function () {
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
        ->deleteJson("/api/v1/assistant/lists/{$list->getHashId()}")
        ->assertForbidden();
});
