<?php

namespace App\Policies;

class StaffAttendancePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'attendance';
    }
}
