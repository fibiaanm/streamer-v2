<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;

class ExpireInvitationsCommand extends Command
{
    protected $signature   = 'invitations:expire';
    protected $description = 'Mark pending invitations past their expiry date as expired';

    public function handle(): void
    {
        $count = Invitation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} invitation(s).");
    }
}
