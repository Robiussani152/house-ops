<?php

namespace App\Filament\Pages;

use App\Models\Staff;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class Payroll extends Page implements HasTable
{
    use InteractsWithTable;
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected string $view = 'filament.pages.payroll';

    /**
     * @var array<int, array{days_present: int, days_absent: int, paid_absence_days: int, tasks_done: int, amount: float}>
     */
    private array $payrolls = [];

    public function table(Table $table): Table
    {
        return $table
            ->query(Staff::query()->where('is_active', true))
            ->defaultSort('name')
            ->paginated(false)
            ->filters([
                SelectFilter::make('month')
                    ->options(fn (): array => collect(range(0, 11))
                        ->mapWithKeys(fn (int $ago): array => [now()->subMonths($ago)->format('Y-m') => now()->subMonths($ago)->format('F Y')])
                        ->all())
                    ->default(now()->format('Y-m'))
                    ->selectablePlaceholder(false)
                    ->query(fn ($query) => $query),
            ], layout: FiltersLayout::AboveContent)
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn (Staff $record): string => $record->avatarUrl()),
                TextColumn::make('name')
                    ->sortable(),
                TextColumn::make('days_present')
                    ->label('Present')
                    ->state(fn (Staff $record): int => $this->payrollFor($record)['days_present']),
                TextColumn::make('days_absent')
                    ->label('Absent')
                    ->state(fn (Staff $record): int => $this->payrollFor($record)['days_absent']),
                TextColumn::make('paid_absence_days')
                    ->label('Paid absence')
                    ->state(fn (Staff $record): int => $this->payrollFor($record)['paid_absence_days']),
                TextColumn::make('tasks_done')
                    ->label('Tasks')
                    ->state(fn (Staff $record): int => $this->payrollFor($record)['tasks_done']),
                TextColumn::make('amount')
                    ->state(fn (Staff $record): string => number_format($this->payrollFor($record)['amount'], 2))
                    ->weight('bold'),
            ])
        ;
    }

    /**
     * @return array{days_present: int, days_absent: int, paid_absence_days: int, tasks_done: int, amount: float}
     */
    private function payrollFor(Staff $staff): array
    {
        // shortcut: one aggregate query per staff member, fine for a small team, batch it if staff grows into the hundreds.
        $month = Carbon::createFromFormat('Y-m-d', ($this->tableFilters['month']['value'] ?? now()->format('Y-m')).'-01') ?: now();

        return $this->payrolls[$staff->id.$month->format('Y-m')] ??= $staff->payrollFor($month);
    }
}
