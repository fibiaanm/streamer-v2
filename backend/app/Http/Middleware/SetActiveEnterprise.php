<?php

namespace App\Http\Middleware;

use App\Domain\Enterprises\Exceptions\EnterpriseHeaderRequiredException;
use App\Domain\Enterprises\Exceptions\EnterpriseNotFoundException;
use App\Domain\Enterprises\Exceptions\EnterpriseNotMemberException;
use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\Subscription;
use App\Services\HashId;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetActiveEnterprise
{
    public function handle(Request $request, Closure $next): Response
    {
        $hashId = $request->header(config('app.enterprise_header', 'X-Enterprise-ID'));

        if (!$hashId) {
            throw new EnterpriseHeaderRequiredException();
        }

        $enterpriseId = HashId::decode($hashId);

        if ($enterpriseId === null) {
            throw new EnterpriseNotFoundException();
        }

        try {
            $enterprise = Enterprise::findOrFail($enterpriseId);
        } catch (ModelNotFoundException) {
            throw new EnterpriseNotFoundException();
        }

        $member = EnterpriseMember::where('enterprise_id', $enterprise->id)
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->with('role.permissions')
            ->first();

        if (!$member) {
            Log::warning('enterprise.not_member', [
                'enterprise_id' => $enterprise->id,
                'user_id'       => auth()->id(),
            ]);
            throw new EnterpriseNotMemberException();
        }

        $subscription = Subscription::where('enterprise_id', $enterprise->id)
            ->active()
            ->with('plan')
            ->latest('starts_at')
            ->first();

        $request->attributes->set('active_enterprise', $enterprise);
        $request->attributes->set('active_enterprise_member', $member);
        $request->attributes->set('active_subscription', $subscription);

        return $next($request);
    }
}
