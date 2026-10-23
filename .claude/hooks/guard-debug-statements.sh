#!/usr/bin/env bash
# PreToolUse hook: block writing leftover debug code or merge-conflict markers into source.
# High-signal only (word-boundary matched so add(/array(/padded don't trigger):
#   PHP/JS: dd(  ray(  console.log(  console.debug(
#   merge conflict markers at start of line: <<<<<<<  =======  >>>>>>>
# Exit 2 blocks the tool call. Edit manually if a match is genuinely intentional.
set -uo pipefail

input=$(cat)
file=$(printf '%s' "$input" | jq -r '.tool_input.file_path // empty')
case "$file" in
  *.php|*.js|*.ts|*.vue|*.jsx|*.tsx) ;;
  *) exit 0 ;;
esac

# Prefer new_string whenever present. A full-file snapshot under contents/content
# must not be scanned when the tool also sends a replacement snippet.
new=$(printf '%s' "$input" | jq -r '.tool_input.new_string // empty' 2>/dev/null || true)
if [ -n "$new" ]; then
  text="$new"
else
  text=$(printf '%s' "$input" | jq -r '
    .tool_input.contents // .tool_input.content //
    .tool_input.file_contents // .tool_input.code // empty' 2>/dev/null || true)
fi
[ -z "$text" ] && exit 0

uncommented=$(printf '%s' "$text" | grep -vE '^[[:space:]]*(//|#)' || true)

problems=""
if printf '%s' "$text" | grep -qE '^(<{7}|={7}|>{7})( |$)'; then
  problems="${problems}  - merge conflict markers\n"
fi
if printf '%s' "$uncommented" | grep -qE '(^|[^[:alnum:]_])(dd|ray)\('; then
  problems="${problems}  - PHP debug call (dd/ray)\n"
fi
if printf '%s' "$uncommented" | grep -qE 'console\.(log|debug)\('; then
  problems="${problems}  - console.log/debug call\n"
fi

if [ -n "$problems" ]; then
  printf "Blocked: this change introduces debug/leftover code into '%s':\n%bRemove it before writing (or edit the file manually if it is intentional)." "$(basename "$file")" "$problems" >&2
  exit 2
fi
exit 0
