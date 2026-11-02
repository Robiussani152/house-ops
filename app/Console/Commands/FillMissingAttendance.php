<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attendance:fill-missing')]
#[Description('Add attendance for active staff the admin has not recorded today')]
class FillMissingAttendance extends Command
{
    public function handle(): int
    {
        $date = now('Asia/Dhaka')->toDateString();

        // shortcut: marks present with base_tasks; change if absent should be the default.
        $count = Staff::query()->where('is_active', true)->get()->filter(
            fn (Staff $staff): bool => StaffAttendance::firstOrCreate(
                ['staff_id' => $staff->id, 'date' => $date],
                ['is_present' => true, 'tasks_done' => $staff->base_tasks],
            )->wasRecentlyCreated,
        )->count();

        $this->info("Added {$count} attendance record(s) for {$date}.");

        return self::SUCCESS;
    }
}
