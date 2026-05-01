<?php

namespace App\Domain\Assistant\Http\Controllers;

use App\Domain\Assistant\Jobs\ProcessAssistantMessage;
use App\Domain\Assistant\Jobs\ProcessMessageAttachment;
use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use App\Http\Formatters\ResponseFormatter;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMessageController
{
    private const SUPPORTED_MIMES = [
        'audio/mpeg', 'audio/ogg', 'audio/webm',
        'image/jpeg', 'image/png', 'image/webp',
        'application/pdf',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'content'    => ['required_without:attachment', 'nullable', 'string', 'max:4096'],
            'attachment' => ['nullable', 'file', 'mimetypes:' . implode(',', self::SUPPORTED_MIMES)],
            'channel'    => ['nullable', 'string', 'in:web,whatsapp'],
            'session_id' => ['nullable', 'string'],
        ]);

        try {
            $conversation = Conversation::firstOrCreate(['user_id' => auth()->id()]);
            $session      = $request->filled('session_id')
                ? $this->resolveSessionById($conversation->id, $request->input('session_id'))
                : $this->resolveActiveSession($conversation->id);

            Log::info('assistant.send_message', [
                'user_id'         => auth()->id(),
                'conversation_id' => $conversation->id,
                'session_id'      => $session->id,
                'has_attachment'  => $request->hasFile('attachment'),
            ]);

            $message = AssistantMessage::create([
                'conversation_id' => $conversation->id,
                'session_id'      => $session->id,
                'role'            => 'user',
                'channel'         => $request->input('channel', 'web'),
                'content'         => $request->input('content', ''),
                'metadata_json'   => ['request_id' => Context::get('request_id')],
            ]);

            $session->incrementMessageCount();

            if ($request->hasFile('attachment')) {
                ProcessMessageAttachment::dispatch(
                    messageId: $message->id,
                    sessionId: $session->id,
                    userId:    auth()->id(),
                )->onQueue('assistant');
                Log::info('assistant.attachment_job_dispatched', ['message_id' => $message->id]);
            } else {
                ProcessAssistantMessage::dispatch(
                    messageId: $message->id,
                    sessionId: $session->id,
                    userId:    auth()->id(),
                )->onQueue('assistant');
                Log::info('assistant.process_job_dispatched', ['message_id' => $message->id]);
            }

            return ResponseFormatter::success([
                'message_id' => $message->getHashId(),
                'session_id' => $session->getHashId(),
                'status'     => 'queued',
            ]);

        } catch (Throwable $e) {
            Log::error('assistant.send_message_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }

    private function resolveSessionById(int $conversationId, string $hashId): AssistantSession
    {
        return AssistantSession::where('id', HashId::decode($hashId))
            ->where('conversation_id', $conversationId)
            ->firstOrFail();
    }

    private function resolveActiveSession(int $conversationId): AssistantSession
    {
        $session = AssistantSession::where('conversation_id', $conversationId)
            ->where('last_message_at', '>', now()->subHours(24))
            ->latest('last_message_at')
            ->first();

        if (!$session) {
            $session = AssistantSession::create([
                'conversation_id' => $conversationId,
                'started_at'      => now(),
                'last_message_at' => now(),
            ]);
        }

        return $session;
    }
}
