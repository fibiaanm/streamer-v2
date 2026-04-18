<?php

namespace App\Domain\Enterprises\Events;

use App\Models\Enterprise;
use App\Models\Invitation;

class InvitationCreated
{
    public function __construct(
        public readonly Enterprise  $enterprise,
        public readonly Invitation  $invitation,
    ) {}
}
