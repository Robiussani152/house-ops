<?php

namespace App\Filament\Widgets;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = now();
        $activeStaff = Staff::query()->where('is_active', true)->get();
        $monthAttendance = StaffAttendance::query()->whereBetween('date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
        $today = StaffAttendance::query()->whereDate('date', $now);

        $absences = (clone $monthAttendance)->where('is_present', false)->get()->groupBy('staff_id');
        $absenceStats = $activeStaff->map(function (Staff $staff) use ($absences): Stat {
            $rows = $absences->get($staff->id, collect());

            return Stat::make($staff->name.' absences', $rows->count())
                ->description($rows->where('is_paid_absence', true)->count().' paid · '.$rows->where('is_paid_absence', false)->count().' unpaid')
                ->color('warning')
            ;
        })->all();

        return [
            Stat::make('Active staff', $activeStaff->count()),
            Stat::make('Present today', (clone $today)->where('is_present', true)->count().' / '.$activeStaff->count())
                ->description((clone $today)->where('is_present', false)->count().' absent'),
            ...$absenceStats,
            Stat::make('Payroll this month', number_format($activeStaff->sum(fn (Staff $staff): float => $staff->payrollFor($now)['amount']), 2))
                ->description($now->format('F Y'))
                ->color('success'),
        ];
    }
}
