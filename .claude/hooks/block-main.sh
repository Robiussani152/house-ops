#!/usr/bin/env bash
# PreToolUse hook: block file edits while on `main` (release-only; every push deploys).
# Exit 2 blocks the tool call and feeds the message back to Claude.
set -uo pipefail

cd "${CLAUDE_PROJECT_DIR:-$(pwd)}" || exit 0

if [ "$(git rev-parse --abbrev-ref HEAD 2>/dev/null)" = "main" ]; then
  echo "Blocked: you are on 'main', which is release-only (git-workflow). Switch to 'develop' or a feature branch before editing files." >&2
  exit 2
fi
exit 0
