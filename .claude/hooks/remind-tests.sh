#!/usr/bin/env bash
# Stop hook: when app/ code changed but no test did, block the stop ONCE per session and ask
# for a Pest test, so the agent is reminded without being trapped in a loop.
set -uo pipefail

sid=$(jq -r '.session_id // "nosid"')
cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0

changed=$(git status --porcelain -uall 2>/dev/null | cut -c4-)
printf '%s\n' "$changed" | grep -qE '^app/.*\.php$' || exit 0
printf '%s\n' "$changed" | grep -qE '^tests/.*\.php$' && exit 0

marker="${TMPDIR:-/tmp}/house-ops-pest-reminder-${sid}"
[ -f "$marker" ] && exit 0
: > "$marker"

echo "app/ code changed but no test under tests/ was added or updated. Write or update a Pest test (pest-test-writer agent) and run it with 'vendor/bin/sail artisan test --compact --filter=<name>'. If a test truly isn't applicable, tell the user why." >&2
exit 2
