---
name: fix-root-cause
description: "Apply when fixing any bug or wrong behavior — stale output, wrong URLs, layout bugs, skip/cache logic, build tooling. Fix the underlying cause, not the symptom; avoid hardcoded paths, selectors, and special-case branches. Always active."
license: MIT
metadata:
  author: project
---

# Fix the cause, not the symptom

When something is wrong (stale output, wrong URLs, layout bugs, skip logic), **find and fix why** before shipping a workaround.

## Do not

- Hardcode **file paths**, Vite chunk names, or manifest filenames for one page/feature.
- Hardcode **CSS selectors**, class names, or string literals to detect "stale" or "wrong" state for a single UI area.
- Add **feature-specific branches** in shared tooling (build, caches) when a generic mechanism works.
- **Exclude or special-case** inputs (e.g. skip inline styles) without understanding why they break the pipeline.
- Rely on **output file mtime alone** to decide freshness when inputs include inline content or hashed assets.

## Do

- **Trace the data flow** (HTML → assets → manifest → inlined CSS → generator) and fix the broken step.
- Use **discovery** from existing config (entrypoints, manifests, URLs on the page) instead of duplicating paths.
- Store **fingerprints** (path + mtime/size, or content hash) of inputs when caching or skipping work.
- Prefer **one general rule** (e.g. `process.argv.includes('serve')`, not `.includes('serve')` on full argv strings).
- Keep fixes **minimal** but correct; match existing project patterns.

## Examples

```php
// ❌ BAD — special-cases one page to hide a stale cache
if ($request->is('dashboard')) { Cache::forget('stats'); }
// ✅ GOOD — invalidate where the data changes
static::saved(fn () => Cache::forget('stats'));
```

If a quick hack seems necessary, **stop**: explain the root cause and propose a proper fix (or ask the user) before implementing.
