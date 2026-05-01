<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require base_path('routes/auth.php');
    require base_path('routes/enterprises.php');
    require base_path('routes/workspaces.php');
    require base_path('routes/assistant.php');
    require base_path('routes/internal.php');
    require base_path('routes/admin.php');
});
