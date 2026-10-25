<?php

namespace App\Policies;

class StaffPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'staff';
    }
}
