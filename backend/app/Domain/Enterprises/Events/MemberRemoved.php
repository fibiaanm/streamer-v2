<?php

namespace App\Domain\Enterprises\Events;

use App\Models\Enterprise;
use App\Models\EnterpriseMember;

class MemberRemoved
{
    public function __construct(
        public readonly Enterprise       $enterprise,
        public readonly EnterpriseMember $member,
    ) {}
}
