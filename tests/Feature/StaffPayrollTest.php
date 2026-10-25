<?php

use App\Filament\Pages\Payroll;
use App\Filament\Resources\Shifts\Pages\ManageShifts;
use App\Filament\Resources\Staff\Pages\ManageStaff;
use App\Filament\Resources\StaffAttendances\Pages\ManageStaffAttendances;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function attend(Staff $staff, string $date, array $attributes = []): StaffAttendance
{
    return StaffAttendance::factory()->create(['staff_id' => $staff->id, 'date' => $date] + $attributes);
}

it('pays per task at salary over 30 days over base tasks', function (): void {
    $staff = Staff::factory()->create();
    attend($staff, '2026-10-01', ['tasks_done' => 3]);
    attend($staff, '2026-10-02', ['tasks_done' => 4]);

    $payroll = $staff->payrollFor(now()->setDate(2026, 10, 15));

    expect($payroll['tasks_done'])->toBe(7)
        ->and($payroll['amount'])->toBe(233.33)
    ;
});

it('pays an absent day only when marked as paid absence', function (): void {
    $staff = Staff::factory()->create();
    attend($staff, '2026-10-01', ['is_present' => false, 'tasks_done' => 0]);
    attend($staff, '2026-10-02', ['is_present' => false, 'tasks_done' => 0, 'is_paid_absence' => true]);

    $payroll = $staff->payrollFor(now()->setDate(2026, 10, 15));

    expect($payroll['days_absent'])->toBe(2)
        ->and($payroll['paid_absence_days'])->toBe(1)
        ->and($payroll['amount'])->toBe(100.0)
    ;
});

it('ignores other months and rounds once on the total', function (): void {
    $staff = Staff::factory()->create();
    attend($staff, '2026-09-30', ['tasks_done' => 3]);
    foreach (range(1, 3) as $day) {
        attend($staff, "2026-10-0{$day}", ['tasks_done' => 1]);
    }

    expect($staff->payrollFor(now()->setDate(2026, 10, 15))['amount'])->toBe(100.0);
});

it('falls back to an initials avatar without an image', function (): void {
    $url = Staff::factory()->make(['name' => 'Jane Doe'])->avatarUrl();

    expect(base64_decode(substr($url, strlen('data:image/svg+xml;base64,'))))->toContain('>JD<');
});

it('rejects a second attendance for the same staff and day', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();
    attend($staff, '2026-10-01');

    Livewire::test(ManageStaffAttendances::class)
        ->callAction('create', ['staff_id' => $staff->id, 'date' => '2026-10-01', 'is_present' => true, 'tasks_done' => 3])
        ->assertHasActionErrors(['date'])
    ;
});

it('renders the admin pages', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();
    attend($staff, now()->toDateString());

    Livewire::test(ManageStaff::class)->assertCanSeeTableRecords([$staff]);
    Livewire::test(ManageShifts::class)->assertOk();
    Livewire::test(Payroll::class)->assertOk()->assertSee($staff->name);
});

it('shows the amount for the selected month on the payroll page', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();
    attend($staff, now()->subMonth()->startOfMonth()->toDateString(), ['tasks_done' => 4]);

    Livewire::test(Payroll::class)
        ->assertDontSee('133.33')
        ->filterTable('month', now()->subMonth()->format('Y-m'))
        ->assertSee('133.33')
    ;
});

it('pays nothing for a staff member without attendance', function (): void {
    expect(Staff::factory()->create()->payrollFor(now()))->toBe([
        'days_present' => 0,
        'days_absent' => 0,
        'paid_absence_days' => 0,
        'tasks_done' => 0,
        'amount' => 0.0,
    ]);
});

it('ignores the paid absence flag on a present day', function (): void {
    $staff = Staff::factory()->create();
    attend($staff, '2026-10-01', ['tasks_done' => 3, 'is_paid_absence' => true]);

    $payroll = $staff->payrollFor(now()->setDate(2026, 10, 15));

    expect($payroll['paid_absence_days'])->toBe(0)
        ->and($payroll['amount'])->toBe(100.0)
    ;
});

it('uses the staff salary and base task count for the rate', function (): void {
    $staff = Staff::factory()->create(['monthly_salary' => 6000, 'base_tasks' => 4]);
    attend($staff, '2026-10-01', ['tasks_done' => 2]);

    expect($staff->payrollFor(now()->setDate(2026, 10, 15))['amount'])->toBe(100.0);
});

it('includes the first and last day of the month only', function (): void {
    $staff = Staff::factory()->create();
    attend($staff, '2026-09-30', ['tasks_done' => 3]);
    attend($staff, '2026-10-01', ['tasks_done' => 3]);
    attend($staff, '2026-10-31', ['tasks_done' => 3]);
    attend($staff, '2026-11-01', ['tasks_done' => 3]);

    $payroll = $staff->payrollFor(now()->setDate(2026, 10, 15));

    expect($payroll['days_present'])->toBe(2)
        ->and($payroll['amount'])->toBe(200.0)
    ;
});

it('uses the uploaded image instead of the initials avatar', function (): void {
    $url = Staff::factory()->make(['image' => 'staff/jane.jpg'])->avatarUrl();

    expect($url)->toContain('staff/jane.jpg')->not->toContain('data:image/svg+xml');
});

it('shows inactive or deleted staff only for months they worked', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $inactive = Staff::factory()->create(['is_active' => false, 'name' => 'Inactive Worker']);
    $idle = Staff::factory()->create(['is_active' => false, 'name' => 'Idle Person']);
    $deleted = Staff::factory()->create(['name' => 'Deleted Worker']);
    attend($inactive, now()->toDateString());
    attend($deleted, now()->toDateString());
    $deleted->delete();

    Livewire::test(Payroll::class)
        ->assertSee('Inactive Worker')
        ->assertSee('Deleted Worker')
        ->assertDontSee('Idle Person')
    ;
});

it('creates staff through the form', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $shift = Shift::factory()->create();

    Livewire::test(ManageStaff::class)
        ->callAction('create', ['name' => 'Jane Doe', 'monthly_salary' => 3000, 'base_tasks' => 3, 'shift_id' => $shift->id, 'is_active' => true])
        ->assertHasNoActionErrors()
    ;

    expect(Staff::where('name', 'Jane Doe')->first())
        ->monthly_salary->toBe('3000.00')
        ->shift_id->toBe($shift->id)
    ;
});

it('validates the staff form', function (array $data, array $errors): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageStaff::class)
        ->callAction('create', $data + ['name' => 'Jane', 'monthly_salary' => 3000, 'base_tasks' => 3])
        ->assertHasActionErrors($errors)
    ;
})->with([
    'missing name' => [['name' => ''], ['name' => 'required']],
    'negative salary' => [['monthly_salary' => -1], ['monthly_salary' => 'min']],
    'zero base tasks' => [['base_tasks' => 0], ['base_tasks' => 'min']],
]);

it('edits staff through the table action', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();

    Livewire::test(ManageStaff::class)
        ->callAction(TestAction::make('edit')->table($staff), ['name' => 'Renamed'])
        ->assertHasNoActionErrors()
    ;

    expect($staff->fresh()->name)->toBe('Renamed');
});

it('creates a shift', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageShifts::class)
        ->callAction('create', ['name' => 'Night', 'starts_at' => '20:00', 'ends_at' => '04:00'])
        ->assertHasNoActionErrors()
    ;

    expect(Shift::where('name', 'Night')->exists())->toBeTrue();
});

it('creates an attendance record', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();

    Livewire::test(ManageStaffAttendances::class)
        ->callAction('create', ['staff_id' => $staff->id, 'date' => '2026-10-01', 'is_present' => true, 'tasks_done' => 4])
        ->assertHasNoActionErrors()
    ;

    expect($staff->attendances()->first())->tasks_done->toBe(4);
});

it('hides tasks when absent and paid absence when present', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageStaffAttendances::class)
        ->mountAction('create')
        ->fillForm(['is_present' => true])
        ->assertSchemaComponentVisible('tasks_done', 'mountedActionSchema0')
        ->assertSchemaComponentHidden('is_paid_absence', 'mountedActionSchema0')
        ->fillForm(['is_present' => false])
        ->assertSchemaComponentHidden('tasks_done', 'mountedActionSchema0')
        ->assertSchemaComponentVisible('is_paid_absence', 'mountedActionSchema0')
    ;
});

it('lets an attendance record be edited without tripping the duplicate rule', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $record = attend(Staff::factory()->create(), '2026-10-01', ['tasks_done' => 3]);

    Livewire::test(ManageStaffAttendances::class)
        ->callAction(TestAction::make('edit')->table($record), ['tasks_done' => 5])
        ->assertHasNoActionErrors()
    ;

    expect($record->fresh()->tasks_done)->toBe(5);
});

it('allows the same date for different staff', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    attend(Staff::factory()->create(), '2026-10-01');
    $other = Staff::factory()->create();

    Livewire::test(ManageStaffAttendances::class)
        ->callAction('create', ['staff_id' => $other->id, 'date' => '2026-10-01', 'is_present' => true, 'tasks_done' => 3])
        ->assertHasNoActionErrors()
    ;
});

it('only offers active staff on the attendance form', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $inactive = Staff::factory()->create(['is_active' => false, 'name' => 'Gone Person']);

    Livewire::test(ManageStaffAttendances::class)
        ->callAction('create', ['staff_id' => $inactive->id, 'date' => '2026-10-01', 'is_present' => true, 'tasks_done' => 3])
        ->assertHasActionErrors(['staff_id'])
    ;
});

it('keeps attendance when staff are deleted and unassigns staff when a shift is deleted', function (): void {
    $shift = Shift::factory()->create();
    $staff = Staff::factory()->create(['shift_id' => $shift->id]);
    attend($staff, '2026-10-01');

    $shift->delete();
    expect($staff->fresh()->shift_id)->toBeNull();

    $staff->delete();
    expect(StaffAttendance::count())->toBe(1)
        ->and(StaffAttendance::first()->staff->is($staff))->toBeTrue()
    ;

    $staff->forceDelete();
    expect(StaffAttendance::count())->toBe(0);
});

it('restores soft-deleted staff from the table', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();
    $staff->delete();

    Livewire::test(ManageStaff::class)
        ->callAction(TestAction::make('restore')->table($staff))
        ->assertHasNoActionErrors()
    ;

    expect($staff->fresh()->trashed())->toBeFalse();
});

it('only lets admins into the panel and its pages', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin/staff')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/admin/staff')->assertOk();

    $this->actingAs(User::factory()->create());
    Livewire::test(Payroll::class)->assertForbidden();
});

it('denies non-admins on the resource policies', function (): void {
    $user = User::factory()->create();
    $staff = Staff::factory()->create();

    expect($user->can('viewAny', Staff::class))->toBeFalse()
        ->and($user->can('update', $staff))->toBeFalse()
        ->and($user->can('viewAny', Shift::class))->toBeFalse()
        ->and($user->can('create', StaffAttendance::class))->toBeFalse()
        ->and(User::factory()->admin()->create()->can('viewAny', Staff::class))->toBeTrue()
    ;
});

it('rejects svg and oversized image uploads', function (string $name, int $kilobytes, string $mime): void {
    $this->actingAs(User::factory()->admin()->create());
    Storage::fake('public');

    Livewire::test(ManageStaff::class)
        ->callAction('create', ['name' => 'Jane', 'monthly_salary' => 3000, 'base_tasks' => 3, 'image' => UploadedFile::fake()->create($name, $kilobytes, $mime)])
        ->assertHasActionErrors(['image'])
    ;
})->with([
    'svg' => ['logo.svg', 10, 'image/svg+xml'],
    'too large' => ['big.jpg', 3000, 'image/jpeg'],
]);

it('rejects values above the column limits', function (array $data, string $field): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageStaff::class)
        ->callAction('create', $data + ['name' => 'Jane', 'monthly_salary' => 3000, 'base_tasks' => 3])
        ->assertHasActionErrors([$field => 'max'])
    ;
})->with([
    'salary' => [['monthly_salary' => 100000000], 'monthly_salary'],
    'base tasks' => [['base_tasks' => 256], 'base_tasks'],
]);

it('rejects more than 255 tasks in a day', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();

    Livewire::test(ManageStaffAttendances::class)
        ->callAction('create', ['staff_id' => $staff->id, 'date' => '2026-10-01', 'is_present' => true, 'tasks_done' => 256])
        ->assertHasActionErrors(['tasks_done' => 'max'])
    ;
});

it('refuses to save a staff member with zero base tasks', function (): void {
    expect(fn () => Staff::factory()->create(['base_tasks' => 0]))->toThrow(InvalidArgumentException::class);
});

it('rejects a month that is not one of the offered options', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(Payroll::class)
        ->set('tableFilters.month.value', 'x')
        ->assertStatus(422)
    ;
});

it('keeps an inactive staff member selectable when editing their old attendance', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create(['is_active' => false]);
    $record = attend($staff, '2026-10-01', ['tasks_done' => 3]);

    Livewire::test(ManageStaffAttendances::class)
        ->callAction(TestAction::make('edit')->table($record), ['tasks_done' => 4])
        ->assertHasNoActionErrors()
    ;

    expect($record->fresh()->tasks_done)->toBe(4);
});

it('builds initials across unicode whitespace', function (): void {
    $url = Staff::factory()->make(['name' => "jane\u{00A0}doe"])->avatarUrl();

    expect(base64_decode(substr($url, strlen('data:image/svg+xml;base64,'))))->toContain('>JD<');
});

it('redirects guests away from the admin pages', function (string $path): void {
    $this->get($path)->assertRedirect();
})->with(['/admin/payroll', '/admin/shifts', '/admin/staff', '/admin/staff-attendances']);

it('stores an uploaded staff image on the public disk', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    Storage::fake('public');

    Livewire::test(ManageStaff::class)
        ->callAction('create', ['name' => 'Jane', 'monthly_salary' => 3000, 'base_tasks' => 3, 'image' => UploadedFile::fake()->image('jane.jpg')])
        ->assertHasNoActionErrors()
    ;

    $image = Staff::where('name', 'Jane')->value('image');

    expect($image)->toStartWith('staff/');
    Storage::disk('public')->assertExists($image);
});

it('rejects a non-image upload', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    Storage::fake('public');

    Livewire::test(ManageStaff::class)
        ->callAction('create', ['name' => 'Jane', 'monthly_salary' => 3000, 'base_tasks' => 3, 'image' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])
        ->assertHasActionErrors(['image'])
    ;

    expect(Staff::count())->toBe(0);
});

it('shows each month only its own amount', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();
    attend($staff, now()->startOfMonth()->toDateString(), ['tasks_done' => 3]);
    attend($staff, now()->subMonth()->startOfMonth()->toDateString(), ['tasks_done' => 4]);

    Livewire::test(Payroll::class)
        ->assertSee('100.00')
        ->assertDontSee('133.33')
        ->filterTable('month', now()->subMonth()->format('Y-m'))
        ->assertSee('133.33')
        ->assertDontSee('100.00')
    ;
});

it('shows zero for a month without attendance', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $staff = Staff::factory()->create();
    attend($staff, now()->toDateString());

    Livewire::test(Payroll::class)
        ->filterTable('month', now()->subMonths(11)->format('Y-m'))
        ->assertSee('0.00')
        ->assertDontSee('100.00')
    ;
});

it('filters attendance by staff and by date', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $first = attend(Staff::factory()->create(), '2026-10-01');
    $second = attend(Staff::factory()->create(), '2026-10-02');

    Livewire::test(ManageStaffAttendances::class)
        ->filterTable('staff', $first->staff_id)
        ->assertCanSeeTableRecords([$first])
        ->assertCanNotSeeTableRecords([$second])
        ->resetTableFilters()
        ->filterTable('date', ['on' => '2026-10-02'])
        ->assertCanSeeTableRecords([$second])
        ->assertCanNotSeeTableRecords([$first])
    ;
});

it('deletes attendance and staff from their tables', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $record = attend(Staff::factory()->create(), '2026-10-01');

    Livewire::test(ManageStaffAttendances::class)
        ->callAction(TestAction::make('delete')->table($record))
        ->assertHasNoActionErrors()
    ;
    expect(StaffAttendance::count())->toBe(0);

    $staff = Staff::factory()->create();
    Livewire::test(ManageStaff::class)
        ->callAction(TestAction::make('delete')->table($staff))
        ->assertHasNoActionErrors()
    ;
    expect($staff->fresh()->trashed())->toBeTrue();
});
