# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@BOOST.md

@AI.local.md

Laravel 13 / PHP 8.5, Filament 5 admin panel at `/admin` (login only, no registration), Tailwind 4 + Vite 8, Pest 4, Pint, Larastan.

## Commands

All commands run through Sail (see `BOOST.md`). Add `</dev/null` to non-interactive runs, since some commands hang on a prompt.

```bash
vendor/bin/sail composer run dev                    # server + Vite
vendor/bin/sail artisan test --compact              # all tests; add --filter=Name for one
vendor/bin/sail composer lint                       # Pint on app config database routes tests
vendor/bin/sail composer analyse                    # PHPStan + Larastan, level 5
vendor/bin/sail artisan make:filament-user          # create an admin user
```

## Conventions

- `pint.json` is ported from another project's PHP CS Fixer config (`empty` preset + `@PhpCsFixer` + `@PHP8x5Migration`). Pint has no custom fixers, so `PedroTroller/line_break_between_method_arguments` is intentionally absent. Pint also rewrites PHPUnit-style test classes, so write Pest-style tests.
- Neither Pint nor PHPStan runs in CI. Run both before committing; the `git-commit-pusher` agent does.
- Git: work on `develop` or a feature branch cut from it, merged into `develop` first. `main` is release-only, and every push to it deploys. Use `/commit-push`, or `/commit-push release` to merge `develop` into `main`.
- Skills live in `.ai/skills/` and are symlinked from `.claude/skills/`; edit them in `.ai/`. `boost:update` refreshes `BOOST.md` and the Boost-managed skills.

## Deployment

Pushing to `main` runs `.github/workflows/deploy.yml`: SSH to Hostinger (PHP 8.5), `git pull --ff-only`, `composer install --no-dev`, `migrate --force`, then cache. Nothing is built in CI or on the server.

- Migrations run on every push to `main`, so make them production-safe.
- The root `.htaccess` rewrites requests into `public/`.
- `public/build` is gitignored and not built on the server. Check how Vite assets reach production before relying on new frontend code.
- Filament assets are gitignored and republished by `filament:upgrade` in composer's `post-autoload-dump`.
- In production `/admin` returns 403 unless `User` implements `FilamentUser` with `canAccessPanel()`. The server also needs `ext-intl`.
