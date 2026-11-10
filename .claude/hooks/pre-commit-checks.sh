#!/usr/bin/env bash
# PreToolUse hook (Bash): before any `git commit` or `git push`, run Pint (check only), PHPStan and the full
# test suite. Exit 2 blocks the command and feeds the output back to Claude.
set -uo pipefail

command=$(jq -r '.tool_input.command // empty')
printf '%s' "$command" | grep -Eq '(^|[;&|(]|[[:space:]])git[[:space:]]+(-[^[:space:]]+[[:space:]]+)*(commit|push)([[:space:]]|$)' || exit 0

cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0

if ! vendor/bin/sail ps -q </dev/null 2>/dev/null | grep -q .; then
  echo "Blocked: Sail is not running, so Pint and PHPStan cannot run. Start it with 'vendor/bin/sail up -d'." >&2
  exit 2
fi

output=$(vendor/bin/sail bin pint --test app config database routes tests </dev/null 2>&1) || {
  { echo "Blocked: Pint found style problems. Run 'vendor/bin/sail bin pint --format agent', then retry:"; printf '%s\n' "$output" | tail -n 30; } >&2
  exit 2
}

output=$(vendor/bin/sail composer analyse </dev/null 2>&1) || {
  { echo "Blocked: PHPStan found problems. Fix them (do not baseline or ignore without cause):"; printf '%s\n' "$output" | tail -n 30; } >&2
  exit 2
}

output=$(vendor/bin/sail artisan test --compact </dev/null 2>&1) || {
  { echo "Blocked: the test suite failed. Fix the failures:"; printf '%s\n' "$output" | tail -n 30 | cut -c1-600; } >&2
  exit 2
}
exit 0
