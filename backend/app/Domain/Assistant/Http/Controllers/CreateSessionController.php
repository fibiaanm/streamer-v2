<?php

namespace App\Domain\Assistant\Http\Controllers;

use App\Domain\Assistant\Http\Resources\AssistantSessionResource;
use App\Domain\Assistant\Models\AssistantSession;
use App\Domain\Assistant\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CreateSessionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $conversation = Conversation::firstOrCreate(['user_id' => $request->user()->id]);

        $session = AssistantSession::create([
            'conversation_id' => $conversation->id,
            'started_at'      => now(),
            'last_message_at' => now(),
        ]);

        return (new AssistantSessionResource($session))
            ->response()
            ->setStatusCode(201);
    }
}
