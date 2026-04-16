<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginPageController extends Controller
{
    private const IMAGES = [
        '/images/login/studio@2000.webp',
    ];

    public function __invoke(Request $request): Response
    {
        $image = self::IMAGES[array_rand(self::IMAGES)];

        return Inertia::render('Auth/Login', [
            'imageUrl' => $image,
        ]);
    }
}
