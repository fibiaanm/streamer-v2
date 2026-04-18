<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Events\MemberRemoved;
use App\Http\Formatters\ResponseFormatter;
use App\Models\EnterpriseMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveMemberController
{
    public function __invoke(Request $request, string $memberId): JsonResponse
    {
        try {
            $enterprise    = $request->attributes->get('active_enterprise');
            $currentMember = $request->attributes->get('active_enterprise_member');

            $target = EnterpriseMember::findByHashId($memberId);

            if (! $target || $target->enterprise_id !== $enterprise->id) {
                return response()->json([
                    'error' => ['code' => 'enterprise.not_member', 'context' => []],
                ], 404);
            }

            if ($target->id === $currentMember->id) {
                return response()->json([
                    'error' => ['code' => 'enterprise.cannot_remove_self', 'context' => []],
                ], 422);
            }

            if ($target->role->name === 'owner' && $target->role->isGlobal()) {
                return response()->json([
                    'error' => ['code' => 'enterprise.cannot_remove_owner', 'context' => []],
                ], 422);
            }

            $target->update(['status' => 'suspended']);

            event(new MemberRemoved($enterprise, $target));

            return ResponseFormatter::noContent();

        } catch (Throwable $e) {
            Log::error('enterprises.remove_member_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
