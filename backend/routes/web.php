<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login',    fn () => Inertia::render('Auth/Login'))->name('login');
Route::get('/register', fn () => Inertia::render('Auth/Register'))->name('register');

// App shell — Vue Router gestiona toda la navegación interna
Route::get('/app/{any?}', fn () => Inertia::render('App'))
    ->where('any', '.*')
    ->name('app');

// Design system — solo local
if (app()->isLocal()) {
    Route::get('/design-test', fn () => Inertia::render('DesignTest'))->name('design-test');
}
