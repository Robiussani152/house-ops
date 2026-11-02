<?php

use App\Models\Staff;
use App\Models\StaffAttendance;

it('adds attendance only for active staff without a record today', function () {
    $this->travelTo(now('Asia/Dhaka')->setTime(23, 30));
    $today = now('Asia/Dhaka')->toDateString();

    $missing = Staff::factory()->create(['base_tasks' => 4]);
    $recorded = Staff::factory()->create();
    $inactive = Staff::factory()->create(['is_active' => false]);
    StaffAttendance::factory()->create(['staff_id' => $recorded->id, 'date' => $today, 'is_present' => false, 'tasks_done' => 0]);

    $this->artisan('attendance:fill-missing')->assertSuccessful();

    expect($missing->attendances()->whereDate('date', $today)->first())
        ->is_present->toBeTrue()
        ->tasks_done->toBe(4)
    ;
    expect($recorded->attendances()->count())->toBe(1)
        ->and($recorded->attendances()->first()->is_present)->toBeFalse()
        ->and($inactive->attendances()->count())->toBe(0)
    ;
});
