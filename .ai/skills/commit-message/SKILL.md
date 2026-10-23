---
name: commit-message
description: "Use when generating a git commit message. Outputs only the commit message — single line, conventional format, max 20 words / 120 characters, no trailers."
license: MIT
metadata:
  author: project
---

# Commit Message

Follow these rules strictly.

## Output

- Output ONLY the message: one line, no body, no extra text. Max 20 words and 120 characters; if longer, rewrite shorter.
- No trailers ever: no `Co-Authored-By`, `Signed-off-by`, or similar.
- Describe all local changes, not just changes involving agents.

## Format

```
<type>(scope): short description
<type>(scope): short description (#<issue-number>)
```

- Append `(#<number>)` only when the GitHub issue number is actually known; never invent one. One reference, on the subject line (it is not a trailer and is exempt from the no-parentheses style rule).

## Style

- Lowercase, imperative mood (add, fix, refactor, remove, rename), no trailing period.
- One logical change: no "and", no commas, no multiple actions or multi-clause sentences.
- Avoid "update", "changes", "for improved", "to standardize", "across the application".

## Valid Examples

```
fix(routes): correct welcome route name
chore(deploy): pull over ssh instead of building in ci
feat(auth): add login throttling (#12)
```
