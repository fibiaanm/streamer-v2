<?php

namespace App\Domain\Assistant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssistantServiceAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token   = config('assistant.service_token');
        $bearer  = $request->bearerToken();

        if (! $token || $bearer !== $token) {
            return response()->json(['error' => ['code' => 'unauthorized']], 401);
        }

        return $next($request);
    }
}
