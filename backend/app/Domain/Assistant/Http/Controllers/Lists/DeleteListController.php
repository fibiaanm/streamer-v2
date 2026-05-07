<?php

namespace App\Domain\Assistant\Http\Controllers\Lists;

use App\Domain\Assistant\Models\AssistantList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DeleteListController extends Controller
{
    public function __invoke(Request $request, AssistantList $list): JsonResponse
    {
        if ($list->user_id !== $request->user()->id) {
            abort(403);
        }

        $list->delete();

        return response()->json(null, 204);
    }
}
