<?php

use App\Http\Controllers\Admin\AdminConversationsController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\UsageBreakdownController;
use App\Http\Controllers\Admin\UsageSummaryController;
use App\Http\Controllers\Admin\UsageTimelineController;
use App\Http\Controllers\Admin\UsageTopUsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth.jwt', 'admin'])->group(function () {
    // Token usage
    Route::get('usage/summary',   UsageSummaryController::class);
    Route::get('usage/timeline',  UsageTimelineController::class);
    Route::get('usage/breakdown', UsageBreakdownController::class);
    Route::get('usage/top-users', UsageTopUsersController::class);

    // Users
    Route::get('users',       [AdminUsersController::class, 'index']);
    Route::get('users/{id}',  [AdminUsersController::class, 'show']);

    // Conversations
    Route::get('conversations', AdminConversationsController::class);
});
