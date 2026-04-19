<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnterpriseInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invitation $invitation,
    ) {}

    public function envelope(): Envelope
    {
        $enterpriseName = $invitation->invitable->name ?? config('app.name');

        return new Envelope(
            subject: "Te han invitado a unirte a {$enterpriseName}",
        );
    }

    public function content(): Content
    {
        $acceptUrl = rtrim(config('app.frontend_url'), '/')
            . '/accept-invitation?token='
            . $this->invitation->token;

        return new Content(
            view: 'emails.enterprise-invitation',
            with: [
                'invitedByName'  => $this->invitation->invitedBy->name,
                'enterpriseName' => $this->invitation->invitable->name ?? config('app.name'),
                'roleName'       => $this->invitation->enterpriseRole->name,
                'acceptUrl'      => $acceptUrl,
                'expiresAt'      => $this->invitation->expires_at->format('d M Y'),
            ],
        );
    }
}
