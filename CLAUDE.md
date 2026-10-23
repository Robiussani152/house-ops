# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@BOOST.md

@AI.local.md

## Status

Laravel 13 on PHP 8.5 with Filament 5 (admin panel at `/admin`), Tailwind 4 + Vite 8, Pest 4, Pint and Larastan. The only application route is `/` (welcome view); `routes/` and `app/` show what has been added since. There is no public registration route, and the admin panel uses `->login()` only.

## Commands

Local development runs in Laravel Sail, so prefix PHP, Artisan, Composer and npm with `vendor/bin/sail` (details in `BOOST.md`). Add `</dev/null` to non-interactive runs, since some commands hang on a prompt.

```bash
vendor/bin/sail up -d
vendor/bin/sail composer run dev                         # runs `php artisan dev` (server + Vite etc.)
vendor/bin/sail artisan test --compact                   # all tests (Pest)
vendor/bin/sail artisan test --filter=ExampleTest        # single test
vendor/bin/sail artisan test tests/Feature/ExampleTest.php
vendor/bin/sail composer lint                            # Pint on app config database routes tests
vendor/bin/sail bin pint --dirty                         # format only changed PHP files
vendor/bin/sail composer analyse                         # PHPStan + Larastan (phpstan.neon, level 5)
vendor/bin/sail npm run build                            # needed if you see "Unable to locate file in Vite manifest"
vendor/bin/sail artisan make:filament-user               # create an admin user
```

## Code style and static analysis

- `pint.json` uses the `empty` preset plus `@PhpCsFixer` and `@PHP8x5Migration`, with the rules ported from another project's PHP CS Fixer config. Custom fixers do not work in Pint, so `PedroTroller/line_break_between_method_arguments` is intentionally absent.
- Pint rewrites PHPUnit-style test classes (camelCase method names, `@internal`/`@coversNothing` docblocks), so prefer Pest-style tests.
- Neither Pint nor PHPStan runs in CI. Run both before committing; the `git-commit-pusher` agent does.

## Git workflow

Work on `develop` or a feature branch cut from `develop`. Feature branches merge into `develop` first. `main` is release-only, and every push to it deploys to production. Use `/commit-push` to commit and push, and `/commit-push release` to merge `develop` into `main`. Commit messages are a single line with no trailers (`commit-message` skill).

## Claude config

- Skills live in `.ai/skills/` and are symlinked from `.claude/skills/`. Edit them in `.ai/`. `boost.json` lists them, and `vendor/bin/sail artisan boost:update` refreshes the Boost-managed ones and `BOOST.md`.
- Project skills: `git-workflow`, `commit-message`, `fix-root-cause`, `senior-engineer-stance`, `filament-development`.
- Agents (`.claude/agents/`): `git-commit-pusher`, `pest-test-writer`, `eloquent-performance-reviewer`, `security-reviewer`, `silent-failure-hunter`. The reviewers are read-only.
- `.mcp.json` runs `vendor/bin/sail artisan boost:mcp`, so Sail must be up for the `laravel-boost` tools (`database-query`, `database-schema`, `search-docs`, ...).

## Deployment

Pushing to `main` triggers `.github/workflows/deploy.yml`. It SSHes into the Hostinger server (PHP 8.5) and does not build in CI. On the server it runs: `artisan down`, `git pull --ff-only`, `composer install --no-dev`, `migrate --force`, cache clear, then config/route/view cache, then `artisan up`.

- Migrations run automatically on every push to `main`, so make them safe to run against production.
- Shared hosting serves from the repo root. The tracked root `.htaccess` rewrites requests into `public/`.
- `public/build` is gitignored and no build runs on the server. Check how the Vite assets reach production before relying on new frontend code there.
- Filament assets (`public/{css,js,fonts}/filament`) are gitignored. They are republished by `filament:upgrade` in composer's `post-autoload-dump` during `composer install`.
- In production, `/admin` returns 403 unless `User` implements `FilamentUser` with `canAccessPanel()`. The server also needs `ext-intl`.
