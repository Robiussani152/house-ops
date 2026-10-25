<?php

namespace App\Filament\Resources\StaffAttendances;

use App\Filament\Resources\StaffAttendances\Pages\ManageStaffAttendances;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class StaffAttendanceResource extends Resource
{
    protected static ?string $model = StaffAttendance::class;
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('staff_id')
                    ->relationship('staff', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
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
                TextInput::make('tasks_done')
                    ->required()
                    ->numeric()
                    ->minValue(0)
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
                    ->query(fn ($query, array $data) => $query->when($data['on'], fn ($q, $d) => $q->whereDate('date', $d))),
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
