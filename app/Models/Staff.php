<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'image', 'monthly_salary', 'base_tasks', 'shift_id', 'is_active'])]
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    use SoftDeletes;
    public const DAYS_PER_MONTH = 30;

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return HasMany<StaffAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    /**
     * The uploaded image, or an inline SVG with the name initials.
     */
    public function avatarUrl(): string
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }

        $initials = collect(preg_split('/\s+/u', trim($this->name)) ?: [])
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('')
        ;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96">'
            .'<rect width="96" height="96" fill="#f59e0b"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="sans-serif" font-size="40" fill="#fff">'
            .e($initials).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Attendance totals and earned amount for the month of the given date.
     *
     * @return array{days_present: int, days_absent: int, paid_absence_days: int, tasks_done: int, amount: float}
     */
    public function payrollFor(CarbonInterface $month): array
    {
        $totals = $this->attendances()
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(is_present), 0) as days_present')
            ->selectRaw('COALESCE(SUM(NOT is_present), 0) as days_absent')
            ->selectRaw('COALESCE(SUM(NOT is_present AND is_paid_absence), 0) as paid_absence_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_present THEN tasks_done ELSE 0 END), 0) as tasks_done')
            ->toBase()
            ->first()
        ;

        $salary = (float) $this->monthly_salary;
        $amount = ($totals->tasks_done * $salary / (self::DAYS_PER_MONTH * $this->base_tasks))
            + ($totals->paid_absence_days * $salary / self::DAYS_PER_MONTH);

        return [
            'days_present' => (int) $totals->days_present,
            'days_absent' => (int) $totals->days_absent,
            'paid_absence_days' => (int) $totals->paid_absence_days,
            'tasks_done' => (int) $totals->tasks_done,
            'amount' => round($amount, 2),
        ];
    }

    protected static function booted(): void
    {
        // payrollFor divides by base_tasks, so a zero must fail when written, not when payroll renders.
        static::saving(function (Staff $staff): void {
            if ($staff->base_tasks < 1) {
                throw new \InvalidArgumentException('base_tasks must be at least 1.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
