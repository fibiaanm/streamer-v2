<?php

namespace App\Domain\Assistant\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveUserFromRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::findOrFail((int) $request->route('userId'));
        auth()->setUser($user);
        $request->setUserResolver(fn ($guard = null) => $user);
        return $next($request);
    }
}
