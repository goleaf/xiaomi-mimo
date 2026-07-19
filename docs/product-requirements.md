# Product Requirements

## Product Contract

Xiaomi Mimo is a workspace-scoped task and collaboration application. It must preserve the existing Laravel 13, Inertia 3, Vue 3, TypeScript, Pinia, Tailwind CSS 4, Reka UI, Fortify, Sanctum, Wayfinder, Pest, Larastan, Pint, and SQLite stack.

## Required Capabilities

- Secure multi-workspace task, project, checklist, comment, label, tag, reminder, attachment, activity, notification, import, export, and backup workflows.
- Explicit owner, admin, and member permissions enforced on the backend.
- List, board, and calendar task workflows with validated filtering, sorting, pagination, bulk operations, and accessible interactions.
- English, Lithuanian, and Russian translations with locale- and timezone-aware formatting.
- Reliable recurring tasks and reminders with bounded, idempotent processing.
- SQLite-only operation, documented tuning, integrity diagnostics, safe backups, and deployment constraints.

## Quality Attributes

Workspace isolation is a security boundary. Controllers remain thin, writes use authorized Form Requests and actions, complex reads use scoped query objects, external JSON uses resources, and all changes receive focused tests plus project-wide quality checks before phase completion.

The numbered prompts in the repository contract define the phased acceptance criteria. Later phases must re-read the current source and applicable documentation before implementation.
