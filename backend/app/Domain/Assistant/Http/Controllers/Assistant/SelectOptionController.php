<?php

namespace App\Domain\Assistant\Http\Controllers\Assistant;

use App\Domain\Assistant\Jobs\ProcessAssistantMessage;
use App\Domain\Assistant\Models\AssistantMessage;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SelectOptionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|string',
        ]);

        $decoded = HashId::decode($request->route('messageId'));
        $message = AssistantMessage::findOrFail($decoded);

        $conversation = $message->conversation;
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $meta = $message->metadata_json ?? [];

        // Idempotent: if already selected, do nothing
        if (isset($meta['selected'])) {
            return response()->json(['ok' => true]);
        }

        $validValues = collect($meta['options'] ?? [])->pluck('value')->all();
        abort_unless(in_array($validated['value'], $validValues, strict: true), 422, 'Invalid option value.');

        $meta['selected'] = $validated['value'];
        $message->update(['metadata_json' => $meta]);

        $session = $message->session;

        $userMessage = AssistantMessage::create([
            'conversation_id' => $message->conversation_id,
            'session_id'      => $session->id,
            'role'            => 'user',
            'channel'         => 'web',
            'content'         => $validated['value'],
            'created_at'      => now(),
        ]);

        ProcessAssistantMessage::dispatch(
            messageId: $userMessage->id,
            sessionId: $session->id,
            userId:    $request->user()->id,
        )->onQueue('assistant');

        return response()->json(['ok' => true]);
    }
}
