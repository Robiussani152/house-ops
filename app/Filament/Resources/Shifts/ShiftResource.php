<?php

namespace App\Filament\Resources\Shifts;

use App\Filament\Resources\Shifts\Pages\ManageShifts;
use App\Models\Shift;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedClock;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TimePicker::make('starts_at')
                    ->required(),
                TimePicker::make('ends_at')
                    ->required(),
            ])
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->time()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->time()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
            'index' => ManageShifts::route('/'),
        ];
    }
}
