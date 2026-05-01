<?php

use App\Domain\Assistant\Http\Controllers\Events\CancelEventController;
use App\Domain\Assistant\Http\Controllers\Events\CreateEventController;
use App\Domain\Assistant\Http\Controllers\Events\DetachEventReferenceController;
use App\Domain\Assistant\Http\Controllers\Events\GetEventsController;
use App\Domain\Assistant\Http\Controllers\Events\SnoozeEventController;
use App\Domain\Assistant\Http\Controllers\Events\UpdateEventController;
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
        Route::post('events/{eventId}/cancel',       CancelEventController::class);
        Route::post('events/{eventId}/snooze',       SnoozeEventController::class);
        Route::delete('events/{eventId}/reference',  DetachEventReferenceController::class);
    });
});
