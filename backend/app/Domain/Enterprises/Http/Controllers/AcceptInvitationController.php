<?php

namespace App\Domain\Enterprises\Http\Controllers;

use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use App\Domain\Auth\Http\AuthCookies;
use App\Domain\Enterprises\Application\UseCases\AcceptInvitationUseCase;
use App\Domain\Enterprises\Exceptions\InvitationExpiredException;
use App\Domain\Enterprises\Exceptions\InvitationInvalidException;
use App\Domain\Enterprises\Exceptions\InvitationMemberExistsException;
use App\Http\Formatters\ResponseFormatter;
use App\Models\Enterprise;
use App\Models\Invitation;
use App\Models\User;
use App\Services\HashId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AcceptInvitationController
{
    public function __construct(private readonly AcceptInvitationUseCase $useCase) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $invitation = Invitation::where('token', $token)
                ->where('invitable_type', Enterprise::class)
                ->first();

            if (!$invitation) {
                throw new InvitationInvalidException();
            }

            if ($invitation->status !== 'pending') {
                throw new InvitationInvalidException();
            }

            if ($invitation->expires_at->isPast()) {
                throw new InvitationExpiredException();
            }

            $userExists = User::where('email', $invitation->email)->exists();

            $request->validate([
                'password' => ['required', 'string', 'min:8'],
                'name'     => $userExists ? ['nullable'] : ['required', 'string', 'max:255'],
            ]);

            $tokens = $this->useCase->execute(
                $invitation,
                $request->input('password'),
                $request->input('name'),
            );

            return ResponseFormatter::success(array_merge($tokens, [
                'enterprise_id' => HashId::encode($invitation->invitable_id),
            ]))
                ->withCookie(AuthCookies::access($tokens['access_token']))
                ->withCookie(AuthCookies::refresh($tokens['refresh_token']));

        } catch (ValidationException $e) {
            throw $e;
        } catch (InvitationInvalidException | InvitationExpiredException | InvitationMemberExistsException | InvalidCredentialsException $e) {
            return ResponseFormatter::error($e);
        } catch (Throwable $e) {
            Log::error('enterprises.accept_invitation_unexpected', ['exception' => $e]);
            return ResponseFormatter::serverError();
        }
    }
}
