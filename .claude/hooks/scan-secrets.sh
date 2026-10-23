#!/usr/bin/env bash
# PreToolUse hook: block writing hardcoded secrets into source files.
# Complements guard-protected-files.sh (which blocks editing .env by name) by
# scanning the *content* being introduced for high-signal secret literals.
# High-signal only, to avoid false positives in a Laravel codebase where
# 'api_key' => env('SERVICE_KEY') is everywhere (env()/config() values are ignored).
# Exit 2 blocks the tool call. Edit manually if a match is genuinely intentional.
set -uo pipefail

input=$(cat)
file=$(printf '%s' "$input" | jq -r '.tool_input.file_path // empty')
[ -z "$file" ] && exit 0

base=$(basename "$file")
case "$base" in
  # Placeholders and docs legitimately contain fake keys.
  *.example|*.md|*.dist) exit 0 ;;
esac

# Text being introduced: Write -> content, Edit -> new_string.
text=$(printf '%s' "$input" | jq -r '.tool_input.content // .tool_input.new_string // empty')
[ -z "$text" ] && exit 0

problems=""
if printf '%s' "$text" | grep -qE 'AKIA[0-9A-Z]{16}'; then
  problems="${problems}  - AWS access key id (AKIA…)\n"
fi
if printf '%s' "$text" | grep -qE '\-\-\-\-\-BEGIN ([A-Z]+ )?PRIVATE KEY\-\-\-\-\-'; then
  problems="${problems}  - private key block (BEGIN … PRIVATE KEY)\n"
fi
if printf '%s' "$text" | grep -qE '(sk|rk)_live_[0-9a-zA-Z]{16,}'; then
  problems="${problems}  - Stripe live secret key (sk_live_/rk_live_)\n"
fi
if printf '%s' "$text" | grep -qE 'gh[pousr]_[0-9A-Za-z]{36,}'; then
  problems="${problems}  - GitHub token (ghp_/gho_/…)\n"
fi
# Secret-named variable assigned a literal string value (not env()/config()).
if printf '%s' "$text" | grep -qiE '(password|passwd|secret|api[_-]?key|access[_-]?token|auth[_-]?token|private[_-]?key)['"'"'"]?[[:space:]]*(=>|=|:)[[:space:]]*['"'"'"][A-Za-z0-9/+_-]{20,}['"'"'"]'; then
  problems="${problems}  - hardcoded secret literal assigned to a credential-named key\n"
fi

if [ -n "$problems" ]; then
  printf "Blocked: this change appears to write a secret into '%s':\n%bMove it to a .env value (env()/config()) before writing, or edit the file manually if this is truly intentional (e.g. a test fixture)." "$base" "$problems" >&2
  exit 2
fi
exit 0
