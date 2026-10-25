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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedKey;
    protected static string|\UnitEnum|null $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                Grid::make(1)
                    ->schema(array_map(
                        fn (string $module, array $definition): Section => Section::make($definition['label'])
                            ->compact()
                            ->schema([
                                CheckboxList::make("permissions.{$module}")
                                    ->hiddenLabel()
                                    ->options(collect($definition['actions'])
                                        ->mapWithKeys(fn (string $action): array => ["{$module}.{$action}" => ucfirst($action)])
                                        ->all())
                                    ->columns(2)
                                    ->bulkToggleable(),
                            ]),
                        array_keys(ModulePolicy::MODULES),
                        ModulePolicy::MODULES,
                    )),
            ])
        ;
    }

    /**
     * @return array<string, mixed>
     */
    public static function fillPermissions(Role $role, array $data): array
    {
        $data['permissions'] = $role->permissions
            ->pluck('name')
            ->groupBy(fn (string $name): string => explode('.', $name)[0])
            ->map(fn ($names): array => $names->values()->all())
            ->all()
        ;

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function saveRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->fill(['name' => $data['name']])->save();
            $role->syncPermissions(Arr::flatten($data['permissions'] ?? []));

            return $role;
        });
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
                EditAction::make()
                    ->modalWidth('3xl')
                    ->mutateRecordDataUsing(fn (Role $record, array $data): array => static::fillPermissions($record, $data))
                    ->using(fn (Role $record, array $data): Role => static::saveRole($record, $data)),
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
