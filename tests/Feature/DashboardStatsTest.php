<?php

use App\Filament\Widgets\StatsOverview;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('summarises staff, attendance and payroll', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create(['monthly_salary' => 3000, 'base_tasks' => 3, 'is_active' => true]);
    StaffAttendance::factory()->create(['staff_id' => $staff->id, 'date' => now(), 'is_present' => true, 'tasks_done' => 3]);

    Livewire::test(StatsOverview::class)
        ->assertSee('1 / 1')
        ->assertSee('100.00')
    ;
});
