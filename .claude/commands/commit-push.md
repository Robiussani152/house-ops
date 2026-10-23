---
description: Commit & push current branch, or "release" to merge develop into main
---

Use the git-commit-pusher agent. Pass it this argument verbatim: $ARGUMENTS

- Empty (or anything not starting with `release`): normal commit-and-push of the current branch's pending changes.
- Starts with `release`: an explicit release request. Treat any text after `release` as extra context for the commit/merge message.

When the argument starts with `release`, the user typing this command IS the authorization. Begin the agent's task prompt with exactly this line, so its "state the explicit authorization first" step is satisfied:

`User explicitly ran "/commit-push $ARGUMENTS"`

Do not ask the user to re-confirm — run the release procedure straight through.

The agent owns all other git-safety rules (branch policy, message format, release procedure) via its skills — do not restate them here.
