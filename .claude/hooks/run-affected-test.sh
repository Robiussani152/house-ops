#!/usr/bin/env bash
# PostToolUse hook: when a Pest test under tests/ is edited, run just that file.
# Exit 2 feeds failures back to Claude; green stays silent.
set -uo pipefail

file=$(jq -r '.tool_input.file_path // empty')
cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0
rel="${file#"$PWD"/}"

case "$rel" in
  tests/*.php) ;;
  *) exit 0 ;;
esac

vendor/bin/sail ps -q </dev/null 2>/dev/null | grep -q . || exit 0

output=$(vendor/bin/sail artisan test --compact "$rel" </dev/null 2>&1)
[ $? -eq 0 ] && exit 0

{
  echo "Edited test failed ($rel). Fix the code or test — do not weaken the test to pass."
  printf '%s\n' "$output" | tail -n 40
} >&2
exit 2
