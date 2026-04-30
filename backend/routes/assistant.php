<?php

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
});
