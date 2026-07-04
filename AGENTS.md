# AGENTS.md

Authoritative instructions for AI coding assistants working in this repository.
Keep this file short, specific, and current. If it conflicts with a casual chat
request, this file wins.

## Working principles

- Prefer the simplest solution that fully solves the task. No speculative code.
- Follow SOLID, KISS and DRY pragmatically — abstract on the second real need, not "for the future".
- Keep controllers thin: accept a request, return a response. Business logic lives in services.
- One responsibility per class and per method. If the description needs an "and", split it.
- Make small, reviewable, surgical changes. Touch only what the task requires; do not refactor untouched code.
- Fix root causes, not symptoms. No workarounds layered on workarounds.
- Match the existing style of the file you are editing.

## Quality gates (PHP)

Before declaring a change done, it must pass:

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```

If the repo uses Docker, run these inside the `app` container.
If autoload breaks, verify namespace mapping and run `composer dump-autoload` first.
(For non-PHP repos, apply the equivalent lint/test/static-analysis gates.)

## Change policy

- Do not add dependencies unless the task truly needs them.
- Do not weaken, skip, or delete checks just to make tests or CI pass.
- Do not commit dead code; mention it instead of silently deleting unrelated code.
- Tests must be honest: no fake/stub methods pretending to work, no trivial asserts
  (`assertTrue(true)`), no assertions weakened to pass. Use dummy secrets in tests.

## Secrets — hard rules

- DO NOT read, open, print, or expose secrets.
- DO NOT ask to edit `.env*` files. DO NOT open or print them.
- Always refer to a variable/token by NAME, never by its value.
- Use dummy/placeholder secrets in tests and examples.
- Real production secrets should not live on any machine running a coding agent.

## Do not touch

- DO NOT modify anything inside `vendor/` or `node_modules/`.
- DO NOT make changes without explicit permission when the task is ambiguous — ask first.

## Scratch / task files

- `.local/` is the only place for AI scratch, notes, and task files.
- `.local/` must never reach git (it is git-ignored).
- Never put real secrets in `.local/` — `.local/secret/` is denied even to the agent.

## Technical enforcement (this file alone is not enough)

This file is guidance to the model, not a technical guarantee — it can be ignored.
The real barrier is `opencode.json` in this repo:

- `permission.task = deny` — subagents disabled (a known bug can let them bypass `deny`).
- `permission.external_directory = deny` — the agent cannot read outside this repo.
- `permission.bash = ask`, `permission.webfetch = ask` — no silent shell or network.
- `read`/`edit`/`glob`/`grep` deny everything dot-prefixed except `.github/`,
  `.opencode/`, `.local/` (and a few config dotfiles), plus deny secrets, keys,
  `vendor/` and `node_modules/`.

Note: `bash` is a separate channel. Once a `bash` command is approved it can read any
file (e.g. `cat .env`), regardless of the `read` denies above — so keep `bash = ask`
and do not approve commands that read secrets.
