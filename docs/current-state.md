# Current State

## Baseline

This document records the repository state inspected on 2026-07-19 before product changes. The source code, migrations, live SQLite schema, route list, and tests are authoritative; generated plans are not.

- Runtime: PHP 8.4, Laravel 13.20.0, Inertia Laravel 3.1.1, Vue 3.5, TypeScript, Pinia, Tailwind CSS 4, Fortify 1.37, Sanctum 4.3, Wayfinder 0.1, Pest 4, Larastan 3, Pint, and SQLite.
- Delivery: Laravel serves Inertia pages; Vue Router, Livewire, Volt, React, Filament, Nova, Redis, and non-SQLite relational databases are outside the supported architecture.
- Domain: users, preferences, workspaces, memberships, projects, todos, checklists, comments, labels, tags, reminders, attachments, activity logs, and notifications.
- Routes: authenticated web/Inertia and JSON mutation routes coexist with an unversioned Sanctum API.
- Persistence: UUID strings are used for domain records. The inspected database reports WAL journal mode and integrity `ok`, but foreign-key enforcement is disabled on the inspected CLI connection.

## Verified Risks

The initial inspection confirmed that the risks listed in the permanent contract are credible and require the evidence-heavy audit in the next phase. Notable examples include duplicated web/API controllers, query-bearing shortcut closures, controller-to-controller resolution, global bulk updates, incomplete workspace scoping, model-accessor queries, inconsistent JSON envelopes, hardcoded frontend strings/locales/URLs, stale local Vue state, shared checklist-item input state, and unsafe backup/restore path handling.

No production behavior was changed in this baseline phase.
