<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Policies\ModulePolicy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedKey;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                CheckboxList::make('permissions')
                    ->relationship('permissions', 'name', fn (Builder $query) => $query->orderBy('id'))
                    ->getOptionLabelFromRecordUsing(function (Permission $permission): string {
                        [$module, $action] = explode('.', $permission->name);

                        return (ModulePolicy::MODULES[$module]['label'] ?? $module).': '.$action;
                    })
                    ->columns(4)
                    ->gridDirection('row')
                    ->bulkToggleable(),
            ])
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['permissions', 'users']))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('permissions_count')
                    ->label('Permissions'),
                TextColumn::make('users_count')
                    ->label('Admins'),
            ])
            ->recordActions([
                // the role row is saved before its permission pivot, so clear the cache again afterwards
                EditAction::make()->after(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions()),
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
            'index' => ManageRoles::route('/'),
        ];
    }
}
