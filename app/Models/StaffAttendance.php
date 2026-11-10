<?php

namespace App\Models;

use Database\Factories\StaffAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_id', 'date', 'is_present', 'absence_type', 'tasks_done', 'is_paid_absence', 'note'])]
class StaffAttendance extends Model
{
    /** @use HasFactory<StaffAttendanceFactory> */
    use HasFactory;
    public const HOLIDAY = 'holiday';
    public const LEAVE = 'leave';

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }

    protected static function booted(): void
    {
        static::saving(function (StaffAttendance $attendance): void {
            if ($attendance->is_present) {
                $attendance->absence_type = null;
            } elseif ($attendance->absence_type === self::LEAVE) {
                // leave is granted by the employer, so it is always paid
                $attendance->is_paid_absence = true;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_present' => 'boolean',
            'is_paid_absence' => 'boolean',
        ];
    }
}
