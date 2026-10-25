<?php

namespace App\Policies;

class ShiftPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'shifts';
    }
}
