<?php

namespace App\Filament\Resources\StaffAttendances;

use App\Filament\Resources\StaffAttendances\Pages\ManageStaffAttendances;
use App\Models\Staff;
use App\Models\StaffAttendance;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class StaffAttendanceResource extends Resource
{
    protected static ?string $model = StaffAttendance::class;
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string|\UnitEnum|null $navigationGroup = 'Staff Management';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('staff_id')
                    ->relationship(
                        'staff',
                        'name',
                        // keep the record's own staff selectable when they were deactivated after the entry
                        fn (Builder $query, ?StaffAttendance $record) => $query->where(fn (Builder $q) => $q
                            ->where('is_active', true)
                            ->when($record, fn (Builder $q) => $q->orWhere('id', $record->staff_id))),
                    )
                    ->searchable()
                    ->preload()
                    ->live()
                    // default the day's tasks to this staff member's base tasks; editing keeps the saved count
                    ->afterStateUpdated(function (Set $set, mixed $state, string $operation): void {
                        $baseTasks = $operation === 'create' && $state ? Staff::whereKey($state)->value('base_tasks') : null;

                        if ($baseTasks !== null) {
                            $set('tasks_done', $baseTasks);
                        }
                    })
                    ->required(),
                DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('staff_id', $get('staff_id')),
                    ),
                Toggle::make('is_present')
                    ->default(true)
                    ->live(),
                Select::make('tasks_done')
                    ->required()
                    // 0 up to base tasks + 5 extra, never below the saved value so editing an old record still works
                    ->options(function (Get $get, ?StaffAttendance $record): array {
                        $baseTasks = Staff::whereKey($get('staff_id'))->value('base_tasks') ?? 3;
                        $tasks = range(0, max($baseTasks + 5, $record === null ? 0 : $record->tasks_done));

                        return array_combine($tasks, $tasks);
                    })
                    ->default(3)
                    ->visible(fn (Get $get): bool => (bool) $get('is_present')),
                Toggle::make('is_paid_absence')
                    ->label('Paid absence')
                    ->visible(fn (Get $get): bool => !$get('is_present')),
                TextInput::make('note'),
            ])
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('staff'))
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('staff.name')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_present')
                    ->boolean(),
                TextColumn::make('tasks_done')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_paid_absence')
                    ->boolean(),
                TextColumn::make('note')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('staff')->relationship('staff', 'name'),
                Filter::make('date')
                    ->schema([DatePicker::make('on')])
                    ->query(fn ($query, array $data) => $query->when($data['on'], fn ($q, $d) => $q->where('date', $d))),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
        ;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStaffAttendances::route('/'),
        ];
    }
}
