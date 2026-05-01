<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdminCommand extends Command
{
    protected $signature   = 'admin:grant {email}';
    protected $description = 'Grant admin access to a user';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email: {$email}");
            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->info("{$email} already has admin access.");
            return self::SUCCESS;
        }

        $user->update(['is_admin' => true]);

        $this->info("Admin access granted to {$email}.");
        return self::SUCCESS;
    }
}
