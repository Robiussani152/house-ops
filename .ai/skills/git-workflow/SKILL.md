---
name: git-workflow
description: "Apply for any git operation — branching, commits, merges, pushes. Work on develop or feature branches merged into develop; main is release-only and deploys to production."
license: MIT
metadata:
  author: project
---

# Git Workflow

## Branches

- Work on `develop` or a feature branch cut from `develop` (`feature/*`, `fix/*`, etc.).
- Feature branches merge into `develop` first. `develop` is merged into `main` only on `/commit-push release`.
- `main` is release-only: never edit files or commit on it. Every push to `main` triggers the deploy workflow (`.github/workflows/deploy.yml`), which pulls on the server and runs `migrate --force`.

## Commits

- Before committing, run `git branch --show-current`. On `main` with ordinary (non-merge) changes, **stop** and move the work to `develop` or a feature branch.
- Commit only when the user asks. Never commit unresolved conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`).
- Message format: `commit-message` skill (single line, no trailers).

## Releasing to main

- Only when the user explicitly runs `/commit-push release`: `git merge develop` into `main`, then `git push origin main` (this deploys).
- Merge and push only — never edit files on `main`. On a merge conflict, abort and report.
- **After the release, check out the working branch** (`develop` or the feature branch). Never leave `main` active.

## Safety

- Never force-push `main`.
- Never change git config.
- Avoid destructive git commands unless explicitly requested.
