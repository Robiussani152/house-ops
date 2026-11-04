# House Ops

Back-office app for running a household: staff, shifts, daily attendance and monthly payroll, managed from a Filament admin panel at `/admin` (login only, no registration).

Laravel 13 · PHP 8.5 · Filament 5 · Tailwind 4 · Vite 8 · Pest 4 · Laravel Sail

## Features

- **Staff** – profiles, shifts, base task count, active flag, soft deletes.
- **Attendance** – daily present/absent and tasks done per staff member.
- **Payroll** – monthly page summarising days present/absent, paid absences, tasks and amount owed.
- **Users & roles** – per-module permissions (Spatie Permission); `is_admin` users are super admins, everyone else needs a role.
- **Nightly fill** – `attendance:fill-missing` runs daily at 23:30 (Asia/Dhaka) and marks active staff with no record for the day as present.

## Setup

```bash
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail composer install
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
vendor/bin/sail artisan make:filament-user
vendor/bin/sail composer run dev      # server + Vite
```

The scheduled command needs a scheduler running (`vendor/bin/sail artisan schedule:work` locally, a cron entry for `schedule:run` in production).

## Development

```bash
vendor/bin/sail artisan test --compact   # tests (add --filter=Name for one)
vendor/bin/sail composer lint            # Pint
vendor/bin/sail composer analyse         # PHPStan + Larastan, level 5
```

Neither Pint nor PHPStan runs in CI, so run both before committing. See `CLAUDE.md` for conventions.

## Branching & deployment

Work on `develop` or a feature branch merged into `develop`. `main` is release-only: every push to it runs `.github/workflows/deploy.yml`, which deploys to Hostinger over SSH (`git pull --ff-only`, `composer install --no-dev`, `migrate --force`, cache). Migrations run on every deploy, so keep them production-safe.

The server needs PHP 8.5 and `ext-intl`, and `User` must implement `FilamentUser` for `/admin` to be reachable in production.
