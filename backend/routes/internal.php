<?php

use App\Domain\Assistant\Http\Controllers\Events\CancelEventController;
use App\Domain\Assistant\Http\Controllers\Events\CreateEventController;
use App\Domain\Assistant\Http\Controllers\Events\DetachEventReferenceController;
use App\Domain\Assistant\Http\Controllers\Events\GetEventsController;
use App\Domain\Assistant\Http\Controllers\Events\SnoozeEventController;
use App\Domain\Assistant\Http\Controllers\Events\UpdateEventController;
use App\Domain\Assistant\Http\Controllers\Events\UpdateSeriesController;
use App\Domain\Assistant\Http\Controllers\Lists\AddToListController;
use App\Domain\Assistant\Http\Controllers\Lists\ClearCompletedItemsController;
use App\Domain\Assistant\Http\Controllers\Lists\CreateListController;
use App\Domain\Assistant\Http\Controllers\Lists\DeleteListController;
use App\Domain\Assistant\Http\Controllers\Lists\GetListController;
use App\Domain\Assistant\Http\Controllers\Lists\GetListsController;
use App\Domain\Assistant\Http\Controllers\Lists\RemoveFromListController;
use App\Domain\Assistant\Http\Controllers\Lists\UpdateListItemController;
use App\Domain\Assistant\Http\Controllers\Internal\GetContextController;
use App\Domain\Assistant\Http\Controllers\Internal\GetMemoriesController;
use App\Domain\Assistant\Http\Controllers\Internal\GetUnprocessedMessagesController;
use App\Domain\Assistant\Http\Controllers\Internal\MarkProcessedController;
use App\Domain\Assistant\Http\Controllers\Internal\RecordTokenUsageController;
use App\Domain\Assistant\Http\Controllers\Internal\RecordUserTokenUsageController;
use App\Domain\Assistant\Http\Controllers\Internal\SaveMessageController;
use App\Domain\Assistant\Http\Controllers\Internal\TypingIndicatorController;
use App\Domain\Assistant\Http\Controllers\Internal\UpsertMemoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal')->middleware('assistant.service')->group(function () {
    // Worker context endpoints (integer session_id from Redis jobs)
    Route::get('context/{sessionId}',            GetContextController::class);
    Route::post('sessions/{sessionId}/typing',   TypingIndicatorController::class);
    Route::post('sessions/{sessionId}/messages', SaveMessageController::class);
    Route::post('sessions/{sessionId}/usage',    RecordTokenUsageController::class);

    // Hash-ID endpoints (accessible from admin tools / other services)
    Route::get('unprocessed-messages/{userId}',       GetUnprocessedMessagesController::class);
    Route::get('memories/{userId}',                   GetMemoriesController::class);
    Route::put('memories/{userId}/{category}',        UpsertMemoryController::class);
    Route::post('mark-processed',                     MarkProcessedController::class);

    // Tool routes — worker calls on behalf of a user (userId in path)
    Route::middleware('assistant.user_route')->prefix('users/{userId}')->group(function () {
        Route::post('usage', RecordUserTokenUsageController::class);
        Route::get('events',                       GetEventsController::class);
        Route::post('events',                      CreateEventController::class);
        Route::patch('events/{eventId}',             UpdateEventController::class);
        Route::patch('events/{eventId}/series',      UpdateSeriesController::class);
        Route::post('events/{eventId}/cancel',       CancelEventController::class);
        Route::post('events/{eventId}/snooze',       SnoozeEventController::class);
        Route::delete('events/{eventId}/reference',  DetachEventReferenceController::class);

        Route::get('lists',                                   GetListsController::class);
        Route::get('lists/{list}',                            GetListController::class);
        Route::post('lists',                                  CreateListController::class);
        Route::delete('lists/{list}',                         DeleteListController::class);
        Route::delete('lists/{list}/items/completed',         ClearCompletedItemsController::class);
        Route::post('lists/{list}/items',                     AddToListController::class);
        Route::patch('lists/{list}/items/{item}',             UpdateListItemController::class);
        Route::delete('lists/{list}/items/{item}',            RemoveFromListController::class);
    });
});
