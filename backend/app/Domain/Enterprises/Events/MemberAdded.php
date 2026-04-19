<?php

namespace App\Domain\Enterprises\Events;

use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\Invitation;

class MemberAdded
{
    public function __construct(
        public readonly Enterprise       $enterprise,
        public readonly EnterpriseMember $member,
        public readonly Invitation       $invitation,
    ) {}
}
