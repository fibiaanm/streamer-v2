<?php

use App\Http\Controllers\Auth\AcceptInvitationPageController;
use App\Http\Controllers\Auth\AppPageController;
use App\Http\Controllers\Auth\LoginPageController;
use App\Http\Controllers\Auth\SwitchPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', LoginPageController::class)->name('login');
Route::get('/switch', SwitchPageController::class)->name('switch');
Route::get('/accept-invitation', AcceptInvitationPageController::class)->name('accept-invitation');
Route::redirect('/register', '/login?mode=signup', 301)->name('register');

// App shell — Vue Router gestiona toda la navegación interna
Route::get('/app/{any?}', AppPageController::class)
    ->where('any', '.*')
    ->name('app');

// Admin shell — misma Inertia app, Vue Router maneja /admin/*
Route::get('/admin/{any?}', AppPageController::class)
    ->where('any', '.*')
    ->name('admin');

// Design system — solo local
if (app()->isLocal()) {
    Route::get('/design-test', fn () => Inertia::render('DesignTest'))->name('design-test');
}

// Image Studio — herramienta auxiliar, sin autenticación
Route::get('/image-studio', fn () => Inertia::render('ImageStudio'))->name('image-studio');
