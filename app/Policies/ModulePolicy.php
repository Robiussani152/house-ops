<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Access to a module is granted per action through "<module>.<action>" permissions.
 * Super admins (users.is_admin) hold every permission, see User::canAccessModule().
 * Accounts and roles are not modules here on purpose, see SuperAdminPolicy.
 */
abstract class ModulePolicy
{
    /**
     * @var array<string, array{label: string, actions: list<string>}>
     */
    public const MODULES = [
        'staff' => ['label' => 'Staff', 'actions' => ['view', 'create', 'update', 'delete']],
        'shifts' => ['label' => 'Shifts', 'actions' => ['view', 'create', 'update', 'delete']],
        'attendance' => ['label' => 'Attendance', 'actions' => ['view', 'create', 'update', 'delete']],
        'payroll' => ['label' => 'Payroll', 'actions' => ['view']],
    ];

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::MODULES as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    public function viewAny(User $user): bool
    {
        return $user->canAccessModule("{$this->module()}.view");
    }

    public function view(User $user, Model $record): bool
    {
        return $user->canAccessModule("{$this->module()}.view");
    }

    public function create(User $user): bool
    {
        return $user->canAccessModule("{$this->module()}.create");
    }

    public function update(User $user, Model $record): bool
    {
        return $user->canAccessModule("{$this->module()}.update");
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->canAccessModule("{$this->module()}.delete");
    }

    public function deleteAny(User $user): bool
    {
        return $user->canAccessModule("{$this->module()}.delete");
    }

    public function restore(User $user, Model $record): bool
    {
        return $user->canAccessModule("{$this->module()}.delete");
    }

    public function restoreAny(User $user): bool
    {
        return $user->canAccessModule("{$this->module()}.delete");
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $user->canAccessModule("{$this->module()}.delete");
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->canAccessModule("{$this->module()}.delete");
    }

    abstract protected function module(): string;
}
