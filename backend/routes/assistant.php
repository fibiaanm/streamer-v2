<?php

use App\Domain\Assistant\Http\Controllers\Events\CancelEventController;
use App\Domain\Assistant\Http\Controllers\Events\CreateEventController;
use App\Domain\Assistant\Http\Controllers\Events\DetachEventReferenceController;
use App\Domain\Assistant\Http\Controllers\Events\GetEventsController;
use App\Domain\Assistant\Http\Controllers\Events\SnoozeEventController;
use App\Domain\Assistant\Http\Controllers\Events\UpdateEventController;
use App\Domain\Assistant\Http\Controllers\GetConversationController;
use App\Domain\Assistant\Http\Controllers\GetMessagesController;
use App\Domain\Assistant\Http\Controllers\GetSessionsController;
use App\Domain\Assistant\Http\Controllers\SendMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('assistant')->middleware(['auth.jwt', 'enterprise', 'assistant.personal'])->group(function () {
    Route::get('conversation',          GetConversationController::class);
    Route::get('conversation/messages', GetMessagesController::class);
    Route::get('sessions',              GetSessionsController::class);
    Route::post('messages',             SendMessageController::class);

    // Events — frontend (JWT only)
    Route::get('events',                       GetEventsController::class);
    Route::post('events',                      CreateEventController::class);
    Route::patch('events/{event}',             UpdateEventController::class);
    Route::post('events/{event}/cancel',       CancelEventController::class);
    Route::post('events/{event}/snooze',       SnoozeEventController::class);
    Route::delete('events/{event}/reference',  DetachEventReferenceController::class);
});
