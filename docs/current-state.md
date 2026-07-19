# Current State

## Audited Baseline

This document records the source-of-truth repository state inspected on 2026-07-19. The source, migrations, live SQLite schema, Artisan route table, and tests supersede generated plans.

- Runtime: PHP 8.4, Laravel 13.20.0, Inertia Laravel 3.1.1, Vue 3.5, TypeScript, Pinia 4, Tailwind CSS 4, Reka UI, Fortify 1.37, Sanctum 4.3, Wayfinder 0.1, Pest 4, Larastan 3, Pint, and SQLite.
- Backend inventory: 31 controllers, 39 actions, 6 services, 15 Form Requests, 12 API Resources, 6 policies, 14 models, 6 enums, 4 commands, 3 providers, 1 notification, 2 middleware, and no application event/listener/observer classes.
- Persistence inventory: 22 migrations, 14 factories, and 12 seeders.
- Frontend inventory: 24 pages, 8 layouts, 156 components, 9 composables, 5 Pinia stores, 8 type modules, a custom route helper, and generated Wayfinder actions/routes.
- Test inventory: 28 PHP files containing 111 Pest tests; phase 0 passed 329 assertions.
- Delivery: Laravel serves Inertia pages; Vue Router, Livewire, Volt, React, Filament, Nova, Redis, and non-SQLite relational databases remain outside the architecture.

## Runtime SQLite State

The Laravel connection reports foreign keys enabled, WAL journal mode, synchronous `NORMAL`, a 5000 ms busy timeout, `DEFERRED` transaction mode, a 1000-page WAL auto-checkpoint, and integrity `ok`. Foreign-key enforcement is connection-specific and was not inherited by a separately opened CLI connection.

The live domain schema contains no foreign-key clauses despite migrations using `uuid()->constrained()`. The sole observed FK is on `passkeys.user_id`, whose integer-affinity type conflicts with the UUID-text user key. Notifications and Sanctum personal access tokens also use integer polymorphic IDs against UUID users. No live `CHECK` constraints enforce roles, statuses, priorities, reminder types, or preference values.

## Highest-Priority Findings

### Critical

- Bulk task updates/deletes validate IDs globally and call `Todo::whereIn(...)` without workspace scope.
- Task relationships and label/tag attachment accept globally existing foreign IDs.
- Checklist, label, tag, reminder, and attachment write paths have missing policy authorization, including authenticated API mutations by global child ID.
- Backup endpoints have no owner policy, expose physical paths, accept filenames, and copy/replace only the main SQLite file while WAL is active.
- Invitations can create a user with the known password `password` and no acceptance token.
- Domain referential integrity is not enforced by the live SQLite schema.

### High

- Workspace membership removal references an undefined request variable and does not protect final ownership.
- Workspace switching writes a session ID that `User::currentWorkspace()` ignores.
- API tokens receive unrestricted abilities and API login lacks an explicit limiter.
- Nested project/task-child binding is not scoped to the route workspace/task.
- Web/API controllers duplicate behavior; one todo method mixes Inertia and JSON by `expectsJson()`; JSON envelopes differ.
- Route closures resolve workspaces, execute todo queries, manufacture props, and call controllers through the service container.
- Model progress accessors query the database; dashboard/project/recurrence paths contain excessive-load or N+1 risks.
- Import/export/upload workflows lack required boundary, payload, content, streaming, and rollback controls.
- Task board mode is selectable but not rendered; drag-and-drop mixes unused `@dnd-kit` imports with inaccessible native behavior.
- Frontend copy, `en-US`, settings URLs, and the custom global `route()` are widespread; detail/checklist state becomes stale or shared.

## Quality Baseline

Pest and the production Vite build pass. Larastan, Vue TypeScript, ESLint, and Prettier checks have existing failures. Prompt 1 records these without changing production behavior; later phases must reduce them while adding focused regression coverage.

## Audit Index

- Backend/controller/routes: `docs/audit/backend.md`
- Frontend: `docs/audit/frontend.md`
- Database/domain integrity: `docs/audit/database.md`
- Security: `docs/audit/security.md`
- Tests/quality: `docs/audit/testing.md`
- Ordered remediation: `docs/implementation-roadmap.md`

No finding is fixed by the audit phase.
