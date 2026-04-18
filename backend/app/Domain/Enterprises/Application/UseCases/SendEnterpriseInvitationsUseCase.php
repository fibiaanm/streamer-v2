<?php

namespace App\Domain\Enterprises\Application\UseCases;

use App\Jobs\SendInvitationEmailJob;
use App\Models\Enterprise;
use App\Models\EnterpriseRole;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SendEnterpriseInvitationsUseCase
{
    /**
     * @param  string[] $emails
     * @return Collection<int, Invitation>
     */
    public function execute(Enterprise $enterprise, User $invitedBy, array $emails): Collection
    {
        $memberRole = EnterpriseRole::whereNull('enterprise_id')
            ->where('name', 'member')
            ->first();

        if (! $memberRole) {
            Log::error('send_enterprise_invitations.member_role_missing');
            throw new RuntimeException('Global member role not found');
        }

        $invitations = collect();

        foreach ($emails as $email) {
            $invitation = $this->createOrRefresh($enterprise, $invitedBy, $memberRole, $email);
            SendInvitationEmailJob::dispatch($invitation->id);
            $invitations->push($invitation);
        }

        return $invitations;
    }

    private function createOrRefresh(
        Enterprise     $enterprise,
        User           $invitedBy,
        EnterpriseRole $memberRole,
        string         $email,
    ): Invitation {
        $existing = Invitation::where('invitable_type', Enterprise::class)
            ->where('invitable_id', $enterprise->id)
            ->where('email', $email)
            ->whereIn('status', ['pending', 'expired'])
            ->first();

        if ($existing) {
            $existing->update([
                'token'      => Str::uuid()->toString(),
                'status'     => 'pending',
                'expires_at' => now()->addDays(7),
            ]);

            return $existing->fresh();
        }

        return Invitation::create([
            'invitable_type'       => Enterprise::class,
            'invitable_id'         => $enterprise->id,
            'invited_by_user_id'   => $invitedBy->id,
            'email'                => $email,
            'enterprise_role_id'   => $memberRole->id,
            'token'                => Str::uuid()->toString(),
            'status'               => 'pending',
            'expires_at'           => now()->addDays(7),
        ]);
    }
}
