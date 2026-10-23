# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@BOOST.md

@AI.local.md

## Status

Fresh Laravel 13 / PHP 8.5 skeleton (Tailwind 4 + Vite 8, Pest 4, Pint, Larastan). So far there is one route (`/` → `welcome` view) and the default `User` model. Check `routes/` and `app/` for what has been added since.

## Commands

Local development runs in Laravel Sail, so prefix PHP, Artisan, Composer and npm with `vendor/bin/sail` (details in `BOOST.md`).

```bash
vendor/bin/sail up -d
vendor/bin/sail composer run dev                         # runs `php artisan dev` (server + Vite etc.)
vendor/bin/sail artisan test                             # all tests (composer test clears config first)
vendor/bin/sail artisan test --filter=ExampleTest        # single test (Pest)
vendor/bin/sail artisan test tests/Feature/ExampleTest.php
vendor/bin/sail bin pint --dirty                         # format changed PHP files
vendor/bin/sail composer analyse                         # PHPStan + Larastan (phpstan.neon, level 5)
vendor/bin/sail npm run build                            # needed if you see "Unable to locate file in Vite manifest"
```

## Boost / MCP

`.mcp.json` runs `vendor/bin/sail artisan boost:mcp`, so Sail must be up for the `laravel-boost` tools (`database-query`, `database-schema`, `search-docs`, ...). `boost.json` lists the installed Boost skills in `.claude/skills/`. Refresh them with `vendor/bin/sail artisan boost:update`.

## Deployment

Pushing to `main` triggers `.github/workflows/deploy.yml`. It SSHes into the Hostinger server and does not build in CI. On the server it runs: `artisan down`, `git pull --ff-only`, `composer install --no-dev`, `migrate --force`, cache clear, then config/route/view cache, then `artisan up`.

- Migrations run automatically on every push to `main`, so make them safe to run against production.
- Shared hosting serves from the repo root. The tracked root `.htaccess` rewrites requests into `public/`.
- `public/build` is gitignored and no build runs on the server. Check how the Vite assets reach production before relying on new frontend code there.
