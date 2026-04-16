<?php

use App\Http\Controllers\Auth\LoginPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', LoginPageController::class)->name('login');
Route::redirect('/register', '/login?mode=signup', 301)->name('register');

// App shell — Vue Router gestiona toda la navegación interna
Route::get('/app/{any?}', fn () => Inertia::render('App'))
    ->where('any', '.*')
    ->name('app');

// Design system — solo local
if (app()->isLocal()) {
    Route::get('/design-test', fn () => Inertia::render('DesignTest'))->name('design-test');
}
