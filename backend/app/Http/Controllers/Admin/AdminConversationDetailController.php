<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Assistant\Models\AssistantSession;
use App\Http\Formatters\ResponseFormatter;
use App\Http\Resources\Admin\AdminSessionDetailResource;
use Illuminate\Http\JsonResponse;

class AdminConversationDetailController
{
    public function __invoke(int $id): JsonResponse
    {
        $session = AssistantSession::with([
            'conversation.user',
            'messages',
        ])->findOrFail($id);

        return ResponseFormatter::success(new AdminSessionDetailResource($session));
    }
}
