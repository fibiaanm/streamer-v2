<?php

use App\Domain\Assistant\Http\Controllers\Internal\GetContextController;
use App\Domain\Assistant\Http\Controllers\Internal\GetMemoriesController;
use App\Domain\Assistant\Http\Controllers\Internal\GetUnprocessedMessagesController;
use App\Domain\Assistant\Http\Controllers\Internal\MarkProcessedController;
use App\Domain\Assistant\Http\Controllers\Internal\SaveMessageController;
use App\Domain\Assistant\Http\Controllers\Internal\TypingIndicatorController;
use App\Domain\Assistant\Http\Controllers\Internal\UpsertMemoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('assistant/internal')->middleware('assistant.service')->group(function () {
    // Worker context endpoints (integer user_id from Redis jobs)
    Route::get('context/{userId}',                         GetContextController::class);
    Route::post('conversations/{conversationId}/typing',   TypingIndicatorController::class);
    Route::post('conversations/{conversationId}/messages', SaveMessageController::class);

    // Hash-ID endpoints (accessible from admin tools / other services)
    Route::get('unprocessed-messages/{userId}',       GetUnprocessedMessagesController::class);
    Route::get('memories/{userId}',                   GetMemoriesController::class);
    Route::put('memories/{userId}/{category}',        UpsertMemoryController::class);
    Route::post('mark-processed',                     MarkProcessedController::class);
});
