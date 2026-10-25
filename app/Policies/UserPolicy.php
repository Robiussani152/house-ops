<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends SuperAdminPolicy
{
    public function delete(User $user, Model $record): bool
    {
        return parent::delete($user, $record) && !$user->is($record);
    }
}
