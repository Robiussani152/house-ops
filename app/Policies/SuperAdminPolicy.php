<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Managing accounts and roles is never delegable: anyone who could edit either could grant themselves more access.
 */
abstract class SuperAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function restore(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function restoreAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $user->is_admin;
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->is_admin;
    }
}
