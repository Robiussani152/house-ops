---
description: Generate grouped release notes for what's on develop but not yet on main
---

Produce release notes for the commits that are on `develop` but not yet released to `main`. This is read-only — do not commit, merge, push, or edit any files.

The user's argument (optional range/context) is: $ARGUMENTS

Steps:

1. Make sure refs are current without changing the working tree: `git fetch origin --quiet` (do not check out or pull). If the fetch fails (offline), proceed with local refs and note that.
2. Determine the range:
   - Default to `main..develop` (commits on develop not yet on main).
   - If `$ARGUMENTS` names a different range or ref (e.g. `v2.3.0..develop` or a branch), use that instead.
3. Read the commits: `git log <range> --no-merges --pretty=format:'%h %s'`.
4. If the range is empty, say so plainly ("main is already up to date with develop — nothing to release") and stop.
5. Otherwise group the commits by their conventional-commit type prefix and present them as markdown:
   - **Features** — `feat`
   - **Fixes** — `fix`
   - **Performance** — `perf`
   - **Refactors** — `refactor`
   - **Other** — `chore`, `docs`, `test`, `build`, `ci`, and anything without a recognized prefix.
   Within each group, one bullet per commit: the cleaned subject (strip the `type(scope):` prefix, keep any trailing `(#123)` issue reference) followed by the short hash.
   Omit empty groups.
6. Add a one-line header with the range and the total commit count.

Output only the formatted release notes. Do not take any git action beyond `fetch` and the read-only `log`.
