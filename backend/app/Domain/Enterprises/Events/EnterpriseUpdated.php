<?php

namespace App\Domain\Enterprises\Events;

use App\Models\Enterprise;

class EnterpriseUpdated
{
    public function __construct(public readonly Enterprise $enterprise) {}
}
