<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffAttendance>
 */
class StaffAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'date' => fake()->unique()->dateTimeThisMonth(),
            'is_present' => true,
            'tasks_done' => 3,
            'is_paid_absence' => false,
        ];
    }
}
