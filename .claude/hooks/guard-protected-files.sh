#!/usr/bin/env bash
# PreToolUse hook: block edits to secret and lock files. Exit 2 blocks the tool call.
set -uo pipefail

file=$(jq -r '.tool_input.file_path // empty')
[ -z "$file" ] && exit 0

base=$(basename "$file")
case "$base" in
  .env.example)      exit 0 ;;
  .env|.env.*)       reason="environment file (may contain secrets)" ;;
  composer.lock)     reason="change it via 'vendor/bin/sail composer require/update', not by hand" ;;
  package-lock.json) reason="change it via 'vendor/bin/sail npm install', not by hand" ;;
  *)                 exit 0 ;;
esac

echo "Blocked: refusing to edit '$base' — $reason. If intended, edit it manually outside Claude." >&2
exit 2
