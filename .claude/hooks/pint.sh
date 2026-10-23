#!/usr/bin/env bash
# PostToolUse hook: format edited PHP files with Pint (via Sail). Never fails the tool call.
set -uo pipefail

file=$(jq -r '.tool_input.file_path // empty')
case "$file" in
  *.php) ;;
  *) exit 0 ;;
esac

cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0
rel="${file#"$PWD"/}"

vendor/bin/sail bin pint "$rel" --quiet </dev/null >/dev/null 2>&1 || true
exit 0
