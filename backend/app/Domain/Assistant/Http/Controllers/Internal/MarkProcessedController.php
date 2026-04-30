<?php

namespace App\Domain\Assistant\Http\Controllers\Internal;

use App\Domain\Assistant\Models\AssistantMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkProcessedController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'message_ids'   => 'required|array',
            'message_ids.*' => 'required|string',
        ]);

        foreach ($request->input('message_ids') as $hashId) {
            $message = AssistantMessage::findByHashId($hashId);
            $message?->update(['memory_processed' => true]);
        }

        return response()->json(['data' => true]);
    }
}
