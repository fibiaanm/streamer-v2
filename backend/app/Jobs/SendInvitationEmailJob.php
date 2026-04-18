<?php

namespace App\Jobs;

use App\Mail\EnterpriseInvitationMail;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendInvitationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $invitationId,
    ) {}

    public function handle(): void
    {
        $invitation = Invitation::with(['invitable', 'enterpriseRole', 'invitedBy'])
            ->find($this->invitationId);

        if (! $invitation) {
            Log::warning('send_invitation_email.not_found', ['invitation_id' => $this->invitationId]);
            return;
        }

        if (! $invitation->isPending()) {
            Log::info('send_invitation_email.skipped_not_pending', ['invitation_id' => $this->invitationId]);
            return;
        }

        Mail::to($invitation->email)->send(new EnterpriseInvitationMail($invitation));
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('send_invitation_email.failed', [
            'invitation_id' => $this->invitationId,
            'exception'     => $exception,
        ]);
    }
}
