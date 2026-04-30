<?php

namespace App\Domain\Assistant\Http\Middleware;

use App\Domain\Assistant\Exceptions\AssistantPersonalEnterpriseRequiredException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePersonalEnterprise
{
    public function handle(Request $request, Closure $next): Response
    {
        $enterprise = $request->attributes->get('active_enterprise');

        if (!$enterprise || $enterprise->type !== 'personal') {
            throw new AssistantPersonalEnterpriseRequiredException();
        }

        return $next($request);
    }
}
