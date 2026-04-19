<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInvitationPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token      = $request->query('token', '');
        $invitation = Invitation::where('token', $token)
            ->where('invitable_type', Enterprise::class)
            ->with(['invitable', 'enterpriseRole'])
            ->first();

        if (!$invitation) {
            return Inertia::render('Auth/AcceptInvitation', ['error' => 'invalid']);
        }

        if ($invitation->status !== 'pending') {
            return Inertia::render('Auth/AcceptInvitation', ['error' => 'invalid']);
        }

        if ($invitation->expires_at->isPast()) {
            return Inertia::render('Auth/AcceptInvitation', ['error' => 'expired']);
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'invitation' => [
                'token'           => $token,
                'email'           => $invitation->email,
                'enterprise_name' => $invitation->invitable->name,
                'role_name'       => $invitation->enterpriseRole->name,
                'user_exists'     => User::where('email', $invitation->email)->exists(),
            ],
        ]);
    }
}
