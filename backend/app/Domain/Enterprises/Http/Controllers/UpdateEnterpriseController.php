<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Enterprises\Events\EnterpriseUpdated;
use App\Http\Formatters\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateEnterpriseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:128'],
        ]);

        try {
            $enterprise = $request->attributes->get('active_enterprise');

            $enterprise->update(['name' => $request->input('name')]);

            event(new EnterpriseUpdated($enterprise));

            return ResponseFormatter::success(['name' => $enterprise->name]);

        } catch (Throwable $e) {
            Log::error('enterprises.update_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
