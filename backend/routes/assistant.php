<?php

use App\Domain\Assistant\Http\Controllers\Events\CancelEventController;
use App\Domain\Assistant\Http\Controllers\Events\CreateEventController;
use App\Domain\Assistant\Http\Controllers\Events\DetachEventReferenceController;
use App\Domain\Assistant\Http\Controllers\Events\GetEventsController;
use App\Domain\Assistant\Http\Controllers\Events\SnoozeEventController;
use App\Domain\Assistant\Http\Controllers\Events\UpdateEventController;
use App\Domain\Assistant\Http\Controllers\Lists\AddToListController;
use App\Domain\Assistant\Http\Controllers\Lists\ClearCompletedItemsController;
use App\Domain\Assistant\Http\Controllers\Lists\CreateListController;
use App\Domain\Assistant\Http\Controllers\Lists\DeleteListController;
use App\Domain\Assistant\Http\Controllers\Lists\GetListController;
use App\Domain\Assistant\Http\Controllers\Lists\GetListsController;
use App\Domain\Assistant\Http\Controllers\Lists\RemoveFromListController;
use App\Domain\Assistant\Http\Controllers\Lists\UpdateListItemController;
use App\Domain\Assistant\Http\Controllers\Assistant\SelectOptionController;
use App\Domain\Assistant\Http\Controllers\CreateSessionController;
use App\Domain\Assistant\Http\Controllers\GetConversationController;
use App\Domain\Assistant\Http\Controllers\GetMessagesController;
use App\Domain\Assistant\Http\Controllers\GetSessionsController;
use App\Domain\Assistant\Http\Controllers\SendMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('assistant')->middleware(['auth.jwt', 'enterprise', 'assistant.personal'])->group(function () {
    Route::get('conversation',          GetConversationController::class);
    Route::get('conversation/messages', GetMessagesController::class);
    Route::get('sessions',              GetSessionsController::class);
    Route::post('sessions',             CreateSessionController::class);
    Route::post('messages',                         SendMessageController::class);
    Route::post('messages/{messageId}/select',      SelectOptionController::class);

    // Lists
    Route::get('lists',                                   GetListsController::class);
    Route::get('lists/{list}',                            GetListController::class);
    Route::post('lists',                                  CreateListController::class);
    Route::delete('lists/{list}',                         DeleteListController::class);
    Route::post('lists/{list}/items',                     AddToListController::class);
    Route::delete('lists/{list}/items/completed',         ClearCompletedItemsController::class);
    Route::patch('lists/{list}/items/{item}',             UpdateListItemController::class);
    Route::delete('lists/{list}/items/{item}',            RemoveFromListController::class);

    // Events — frontend (JWT only)
    Route::get('events',                       GetEventsController::class);
    Route::post('events',                      CreateEventController::class);
    Route::patch('events/{event}',             UpdateEventController::class);
    Route::post('events/{event}/cancel',       CancelEventController::class);
    Route::post('events/{event}/snooze',       SnoozeEventController::class);
    Route::delete('events/{event}/reference',  DetachEventReferenceController::class);
});
