#!/usr/bin/env bash
# PostToolUse hook: run PHPStan/Larastan on an edited PHP file under app/ (or other analysed
# paths). Exit 2 feeds errors back to Claude. Silent when clean or when Sail is not running.
set -uo pipefail

file=$(jq -r '.tool_input.file_path // empty')
case "$file" in
  *.php) ;;
  *) exit 0 ;;
esac

cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0
rel="${file#"$PWD"/}"
case "$rel" in
  app/*|database/*|routes/*) ;;  # tests/ are covered by run-affected-test.sh
  *) exit 0 ;;
esac

vendor/bin/sail ps -q </dev/null 2>/dev/null | grep -q . || exit 0

output=$(vendor/bin/sail bin phpstan analyse "$rel" --memory-limit=1G --no-progress --error-format=raw </dev/null 2>&1)
[ $? -eq 0 ] && exit 0

{
  echo "Larastan found problems in $rel. Fix them (do not baseline or ignore without cause):"
  printf '%s\n' "$output" | tail -n 30
} >&2
exit 2
