<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // is_admin is deliberately not mass assignable
            CreateAction::make()->using(function (array $data): User {
                // second layer behind UserPolicy: only a super admin may set the flag
                if (!auth()->user()?->is_admin) {
                    unset($data['is_admin']);
                }

                return User::forceCreate($data);
            }),
        ];
    }
}
