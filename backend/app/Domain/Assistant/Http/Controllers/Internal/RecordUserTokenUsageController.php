<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\TokenUsage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecordUserTokenUsageController
{
    public function __invoke(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'type'          => 'required|string|in:text,image,embedding,memory,audio',
            'provider'      => 'required|string|max:30',
            'model'         => 'required|string|max:80',
            'input_tokens'  => 'nullable|integer|min:0',
            'output_tokens' => 'nullable|integer|min:0',
            'units'         => 'nullable|integer|min:0',
            'iteration'     => 'integer|min:1|max:255',
        ]);

        $user = User::findOrFail($userId);

        TokenUsage::create([
            'user_id'         => $user->id,
            'conversation_id' => null,
            'session_id'      => null,
            'type'            => $request->input('type'),
            'provider'        => $request->input('provider'),
            'model'           => $request->input('model'),
            'input_tokens'    => $request->input('input_tokens'),
            'output_tokens'   => $request->input('output_tokens'),
            'units'           => $request->input('units'),
            'iteration'       => $request->input('iteration', 1),
            'request_id'      => $request->header('X-Request-Id'),
            'created_at'      => now(),
        ]);

        Log::info('assistant.token_usage_recorded', [
            'user_id'       => $user->id,
            'type'          => $request->input('type'),
            'model'         => $request->input('model'),
            'input_tokens'  => $request->input('input_tokens'),
            'output_tokens' => $request->input('output_tokens'),
        ]);

        return response()->json(['data' => ['ok' => true]]);
    }
}
