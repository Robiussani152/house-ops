<?php

use App\Filament\Pages\Payroll;
use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Filament\Resources\Staff\Pages\ManageStaff;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Staff;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * @param list<string> $permissions
 */
function userWith(array $permissions): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::create(['name' => 'role-'.uniqid()])->givePermissionTo($permissions));

    return $user;
}

it('seeds a view, create, update and delete permission per module', function (): void {
    expect(Permission::pluck('name')->all())->toContain('staff.view', 'staff.delete', 'shifts.update', 'payroll.view')
        ->not->toContain('users.update', 'roles.update')
        ->and(Permission::count())->toBe(13)
    ;
});

it('lets a user with no role and no admin flag nowhere near the panel', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin/staff')->assertForbidden();
});

it('gives a role access only to the modules it grants', function (): void {
    $this->actingAs(userWith(['staff.view']));

    $this->get('/admin/staff')->assertOk();
    $this->get('/admin/shifts')->assertForbidden();
    $this->get('/admin/staff-attendances')->assertForbidden();
    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/roles')->assertForbidden();
});

it('limits actions inside a module to the granted permissions', function (): void {
    $viewer = userWith(['staff.view']);
    $this->actingAs($viewer);
    $staff = Staff::factory()->create();

    Livewire::test(ManageStaff::class)
        ->assertCanSeeTableRecords([$staff])
        ->assertActionHidden('create')
        ->assertActionHidden(TestAction::make('edit')->table($staff))
        ->assertActionHidden(TestAction::make('delete')->table($staff))
    ;

    $this->actingAs(userWith(['staff.view', 'staff.create', 'staff.update', 'staff.delete']));

    Livewire::test(ManageStaff::class)
        ->assertActionVisible('create')
        ->assertActionVisible(TestAction::make('edit')->table($staff))
        ->assertActionVisible(TestAction::make('delete')->table($staff))
    ;
});

it('gates the payroll page on payroll.view', function (): void {
    $this->actingAs(userWith(['staff.view']));
    Livewire::test(Payroll::class)->assertForbidden();

    $this->actingAs(userWith(['payroll.view']));
    Livewire::test(Payroll::class)->assertOk();
});

it('lets a super admin through every module without roles', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    foreach (['staff', 'shifts', 'staff-attendances', 'users', 'roles', 'payroll'] as $path) {
        $this->get("/admin/{$path}")->assertOk();
    }
});

it('creates a role with permissions', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $ids = Permission::whereIn('name', ['staff.view', 'payroll.view'])->pluck('id')->all();

    Livewire::test(ManageRoles::class)
        ->callAction('create', ['name' => 'Supervisor', 'permissions' => $ids])
        ->assertHasNoActionErrors()
    ;

    expect(Role::findByName('Supervisor')->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['payroll.view', 'staff.view'])
    ;
});

it('edits a role and replaces its permissions', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $role = Role::create(['name' => 'Supervisor'])->givePermissionTo('staff.view');
    $new = Permission::where('name', 'shifts.view')->value('id');

    Livewire::test(ManageRoles::class)
        ->callAction(TestAction::make('edit')->table($role), ['name' => 'Lead', 'permissions' => [$new]])
        ->assertHasNoActionErrors()
    ;

    expect($role->fresh()->name)->toBe('Lead')
        ->and($role->fresh()->permissions->pluck('name')->all())->toBe(['shifts.view'])
    ;
});

it('rejects a duplicate role name', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    Role::create(['name' => 'Supervisor']);

    Livewire::test(ManageRoles::class)
        ->callAction('create', ['name' => 'Supervisor'])
        ->assertHasActionErrors(['name' => 'unique'])
    ;
});

it('deletes a role and the users keep their account', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $user = userWith(['staff.view']);
    $role = $user->roles()->first();

    Livewire::test(ManageRoles::class)
        ->callAction(TestAction::make('delete')->table($role))
        ->assertHasNoActionErrors()
    ;

    expect(Role::count())->toBe(0)
        ->and($user->fresh()->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse()
    ;
});

it('assigns roles when creating and editing an admin account', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $role = Role::create(['name' => 'Supervisor']);

    Livewire::test(ManageUsers::class)
        ->callAction('create', ['name' => 'Sam', 'email' => 'sam@example.com', 'password' => 'Secret-pass1', 'roles' => [$role->id]])
        ->assertHasNoActionErrors()
    ;

    $sam = User::where('email', 'sam@example.com')->first();
    expect($sam->hasRole('Supervisor'))->toBeTrue()
        ->and($sam->is_admin)->toBeFalse()
    ;

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($sam), ['roles' => []])
        ->assertHasNoActionErrors()
    ;

    expect($sam->fresh()->roles)->toHaveCount(0);
});

it('lets an account with a role into the panel', function (): void {
    expect(userWith(['staff.view'])->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue();
});

it('keeps accounts and roles super-admin only even for a role holding every permission', function (): void {
    $everything = userWith(Permission::pluck('name')->all());
    $this->actingAs($everything);

    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/roles')->assertForbidden();
    Livewire::test(ManageUsers::class)->assertForbidden();
    Livewire::test(ManageRoles::class)->assertForbidden();
});

it('stops a role holder from reaching the admin form that sets the super admin flag', function (): void {
    $super = User::factory()->admin()->create();
    $holder = userWith(Permission::pluck('name')->all());
    $this->actingAs($holder);

    Livewire::test(ManageUsers::class)->assertForbidden();

    expect($holder->can('create', User::class))->toBeFalse()
        ->and($holder->can('update', $super))->toBeFalse()
        ->and($holder->can('delete', $super))->toBeFalse()
        ->and($holder->fresh()->is_admin)->toBeFalse()
    ;
});

it('applies a role permission change immediately', function (): void {
    $member = userWith(['staff.view']);
    $role = $member->roles()->first();
    expect($member->can('staff.view'))->toBeTrue();

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ManageRoles::class)
        ->callAction(TestAction::make('edit')->table($role), ['name' => $role->name, 'permissions' => []])
        ->assertHasNoActionErrors()
    ;

    expect(User::find($member->id)->can('staff.view'))->toBeFalse();
});
