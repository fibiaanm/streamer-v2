<?php

namespace App\Domain\Enterprises\Events;

use App\Models\Enterprise;
use App\Models\EnterpriseRole;

class RoleCreated
{
    public function __construct(
        public readonly Enterprise     $enterprise,
        public readonly EnterpriseRole $role,
    ) {}
}
