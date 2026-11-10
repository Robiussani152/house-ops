#!/usr/bin/env bash
# PreToolUse hook (Bash): before any `git commit`, run Pint (check only) and PHPStan over the whole
# project. Exit 2 blocks the commit and feeds the output back to Claude.
set -uo pipefail

command=$(jq -r '.tool_input.command // empty')
printf '%s' "$command" | grep -Eq '(^|[;&|(]|[[:space:]])git[[:space:]]+(-[^[:space:]]+[[:space:]]+)*commit([[:space:]]|$)' || exit 0

cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0

if ! vendor/bin/sail ps -q </dev/null 2>/dev/null | grep -q .; then
  echo "Commit blocked: Sail is not running, so Pint and PHPStan cannot run. Start it with 'vendor/bin/sail up -d'." >&2
  exit 2
fi

output=$(vendor/bin/sail bin pint --test app config database routes tests </dev/null 2>&1) || {
  { echo "Commit blocked: Pint found style problems. Run 'vendor/bin/sail bin pint --format agent', then commit again:"; printf '%s\n' "$output" | tail -n 30; } >&2
  exit 2
}

output=$(vendor/bin/sail composer analyse </dev/null 2>&1) || {
  { echo "Commit blocked: PHPStan found problems. Fix them (do not baseline or ignore without cause):"; printf '%s\n' "$output" | tail -n 30; } >&2
  exit 2
}
exit 0
