<?php

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('creates an admin with a hashed password and the admin flag', function (): void {
    Livewire::test(ManageUsers::class)
        ->callAction('create', ['name' => 'New Admin', 'email' => 'new@example.com', 'password' => 'Secret-pass1', 'is_admin' => true])
        ->assertHasNoActionErrors()
    ;

    $user = User::where('email', 'new@example.com')->first();

    expect($user->is_admin)->toBeTrue()
        ->and(Hash::check('Secret-pass1', $user->password))->toBeTrue()
        ->and($user->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue()
    ;
});

it('can create a non-admin account that cannot enter the panel', function (): void {
    Livewire::test(ManageUsers::class)
        ->callAction('create', ['name' => 'Viewer', 'email' => 'viewer@example.com', 'password' => 'Secret-pass1', 'is_admin' => false])
        ->assertHasNoActionErrors()
    ;

    expect(User::where('email', 'viewer@example.com')->first()->is_admin)->toBeFalse();
});

it('validates the admin form', function (array $data, array $errors): void {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(ManageUsers::class)
        ->callAction('create', $data + ['name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'Secret-pass1'])
        ->assertHasActionErrors($errors)
    ;
})->with([
    'missing name' => [['name' => ''], ['name' => 'required']],
    'bad email' => [['email' => 'nope'], ['email' => 'email']],
    'duplicate email' => [['email' => 'taken@example.com'], ['email' => 'unique']],
    'short password' => [['password' => 'Short1a'], ['password']],
    'no uppercase' => [['password' => 'lowercase-pass1'], ['password']],
    'no number' => [['password' => 'Password-without'], ['password']],
    'missing password' => [['password' => ''], ['password' => 'required']],
]);

it('keeps the password when the edit form leaves it blank', function (): void {
    $user = User::factory()->admin()->create(['password' => 'original-pass']);
    $hash = $user->password;

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($user), ['name' => 'Renamed', 'password' => ''])
        ->assertHasNoActionErrors()
    ;

    expect($user->fresh())->name->toBe('Renamed')->password->toBe($hash);
});

it('changes the password and revokes admin through the edit form', function (): void {
    $user = User::factory()->admin()->create();

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($user), ['password' => 'Brand-new-pass1', 'is_admin' => false])
        ->assertHasNoActionErrors()
    ;

    expect(Hash::check('Brand-new-pass1', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->is_admin)->toBeFalse()
    ;
});

it('lets an admin keep their own email on edit without a unique error', function (): void {
    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($this->admin), ['name' => 'Me'])
        ->assertHasNoActionErrors()
    ;
});

it('does not let an admin demote themselves', function (): void {
    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($this->admin), ['is_admin' => false])
        ->assertHasNoActionErrors()
    ;

    expect($this->admin->fresh()->is_admin)->toBeTrue();
});

it('does not let an admin delete themselves but can delete others', function (): void {
    $other = User::factory()->admin()->create();

    Livewire::test(ManageUsers::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->admin))
        ->callAction(TestAction::make('delete')->table($other))
        ->assertHasNoActionErrors()
    ;

    expect(User::find($this->admin->id))->not->toBeNull()
        ->and(User::find($other->id))->toBeNull()
    ;
});

it('keeps the current admin when bulk deleting everyone', function (): void {
    User::factory()->admin()->count(2)->create();

    Livewire::test(ManageUsers::class)
        ->selectTableRecords(User::pluck('id')->all())
        ->callAction(TestAction::make('delete')->table()->bulk())
    ;

    expect(User::pluck('id')->all())->toBe([$this->admin->id]);
});

it('keeps admin management away from non-admins', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
});
