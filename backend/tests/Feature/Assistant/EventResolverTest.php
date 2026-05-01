<?php

use App\Domain\Assistant\Models\AssistantEvent;
use App\Domain\Assistant\Support\EventResolver;

it('resolves a real event by hash id', function () {
    [$user] = asstCtx();
    $event  = AssistantEvent::factory()->create(['user_id' => $user->id]);

    $resolved = EventResolver::resolve($event->getHashId(), $user->id);

    expect($resolved->isVirtual())->toBeFalse();
    expect($resolved->model()->id)->toBe($event->id);
});

it('throws 404 for real event belonging to another user', function () {
    [$user]  = asstCtx();
    [$other] = asstCtx();
    $event   = AssistantEvent::factory()->create(['user_id' => $other->id]);

    EventResolver::resolve($event->getHashId(), $user->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('resolves a virtual event id to a virtual resolved event', function () {
    [$user] = asstCtx();
    $master = AssistantEvent::factory()->master()->create(['user_id' => $user->id]);

    $id       = "v_{$master->id}_2026-06-09";
    $resolved = EventResolver::resolve($id, $user->id);

    expect($resolved->isVirtual())->toBeTrue();
    expect($resolved->seriesId())->toBe($master->id);
    expect($resolved->occurrenceAt()->toDateString())->toBe('2026-06-09');
    expect($resolved->master()->id)->toBe($master->id);
});

it('throws 404 for virtual event with master belonging to another user', function () {
    [$user]  = asstCtx();
    [$other] = asstCtx();
    $master  = AssistantEvent::factory()->master()->create(['user_id' => $other->id]);

    EventResolver::resolve("v_{$master->id}_2026-06-09", $user->id);
})->throws(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

it('throws 404 for virtual event with non-existent series_id', function () {
    [$user] = asstCtx();

    EventResolver::resolve('v_999999_2026-06-09', $user->id);
})->throws(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
