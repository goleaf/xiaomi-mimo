# Workspace Management Center Design

Date: 2026-07-20

## Goal

Turn the existing `/workspaces` portfolio into the central workspace management module. The module must provide complete, workspace-safe CRUD for workspaces, members, labels, tags, task statuses, and task priorities through both the Inertia UI and the existing API.

The implementation preserves Laravel 13, Inertia 3, Vue 3, TypeScript, Tailwind CSS 4, Wayfinder, Sanctum, Pest, and SQLite.

## Existing State

The live `/workspaces` page currently supports workspace creation only. Backend routes already exist for workspace update, delete, and switch, but the page does not expose them. The displayed member total is incorrect because the workspace collection does not load `members_count`.

Labels and tags already use workspace-owned tables and task pivot tables, but their mutation paths need stronger authorization and workspace-scoped validation. Task statuses and priorities are fixed PHP enums stored as strings on `todos`, so making them configurable requires a coordinated schema and application migration.

The application has an existing unversioned Sanctum API. The management center will extend that API while preserving current routes and response compatibility where possible.

## Information Architecture

### Workspace Portfolio

`/workspaces` remains the portfolio entry point and provides:

- workspace creation;
- search and sorting;
- accurate workspace, member, project, and task totals;
- a visible current-workspace indicator;
- switch, open, edit, duplicate, and delete actions;
- permission-aware action states;
- empty, loading, validation, network-error, and success states.

Each workspace card opens its management view at `/workspaces/{workspace}`.

### Workspace Management View

The management view uses the existing authenticated application shell and contains these sections:

1. **Overview** — name, description, ownership, timestamps, usage totals, current-workspace selection, and links to workspace projects and tasks.
2. **Members** — invite, role change, removal, and ownership transfer.
3. **Task configuration** — labels, tags, statuses, and priorities with usage counts and ordering controls.
4. **Danger zone** — ownership transfer and permanent workspace deletion with explicit confirmation.

The sections remain addressable through generated Wayfinder routes. The Vue page may use tabs or segmented navigation, but URL state must remain shareable and browser navigation must work.

## Domain Model

### Workspaces

The existing workspace table remains authoritative. Create, read, update, switch, duplicate, ownership transfer, and delete operations use focused actions and policies.

Workspace duplication copies the workspace definition and configurable task metadata only. It does not copy members, projects, tasks, comments, files, reminders, or activity history.

### Members

Workspace membership continues to use `workspace_members` and `WorkspaceRole`. The management center adds role updates and ownership transfer while preserving the rule that every workspace has exactly one owner.

Ownership transfer is transactional: the selected member becomes owner, the previous owner becomes admin, and `workspaces.owner_id` plus both membership rows change together.

### Labels and Tags

The existing `labels`, `tags`, `todo_label`, and `todo_tag` tables remain in use.

- Names are unique inside a workspace.
- Labels retain editable colors.
- Lists include task usage counts.
- Deleting a label or tag explicitly detaches it from affected tasks in the same transaction.
- All child records are resolved through their parent workspace rather than global binding.

### Task Statuses

Add a workspace-owned `task_statuses` table containing:

- UUID primary key;
- workspace ID;
- stable workspace-local key;
- editable name and color;
- display position;
- `is_default` flag;
- `is_completed` semantic flag;
- `is_archived` flag;
- timestamps.

Every workspace must retain one default status and at least one completed status. Names and colors are presentation data; completion behavior depends on `is_completed`.

### Task Priorities

Add a workspace-owned `task_priorities` table containing:

- UUID primary key;
- workspace ID;
- stable workspace-local key;
- editable name and color;
- display position and numeric weight;
- `is_default` flag;
- `is_archived` flag;
- timestamps.

Every workspace must retain one default priority. Sorting uses stored weight and position rather than hardcoded enum cases.

### Todo Migration

Add nullable `status_id` and `priority_id` columns first. Create the canonical three statuses and five priorities for every existing workspace, backfill tasks by workspace and legacy value, verify complete mapping, and then enforce required foreign keys and indexes.

Legacy `status` and `priority` response fields remain scalar stable keys during compatibility migration. New resources also expose the full status and priority definitions. Dropping legacy columns is deferred until all application paths and external consumers have migrated.

## Behavior and Data Flow

Controllers remain thin. Form Requests handle authorization and validation. Actions own every state change, including workspace duplication, ownership transfer, task-field reorder, and delete-with-replacement. API Resources normalize output for both web props and API responses where appropriate.

All related identifiers are validated through the authorized workspace. Mixed or foreign workspace identifiers fail before any write. Multi-record changes run inside a database transaction.

Changing a task to a completed status sets `completed_at`; moving it to a non-completed status clears `completed_at`. All task mutation paths use the same transition action so this invariant cannot be bypassed.

Deleting an in-use status or priority requires selecting a replacement from the same workspace. Reassignment and deletion occur atomically. Archiving hides a definition from new selection while preserving existing task relationships.

## Authorization

- Any workspace member may view the workspace and available task definitions.
- Members may use active definitions on tasks they are authorized to update.
- Admins may edit workspace details, manage non-owner memberships, and manage task definitions.
- Only the owner may transfer ownership or delete the workspace.
- No user may remove or demote the sole owner outside the ownership-transfer action.

Policies and authorized Form Requests enforce these rules for both browser and API requests. Frontend visibility is not an authorization boundary.

## API

The existing API is present, so every management operation receives an API surface backed by the same actions and policies as the web flow.

The scope includes:

- complete workspace CRUD, switch, duplicate, and ownership transfer;
- member list, invite, role update, and removal;
- label and tag CRUD;
- status and priority CRUD, archive, reorder, and delete-with-replacement;
- task payloads that expose both compatibility keys and task-definition resources.

Routes are nested beneath their workspace and use scoped binding or explicit relationship lookup. New endpoints use consistent resource envelopes, validation errors, status codes, bounded collections, and Sanctum ability checks. Existing unversioned routes remain compatible while the repository's broader `/api/v1` migration stays a separate concern.

The implementation also fixes authorization, workspace scoping, and atomicity gaps in the touched task and metadata paths. Unrelated comments, checklist, reminder, attachment, activity, and notification API redesign remains outside this feature.

## Error and Deletion Handling

- Validation errors are attached to their exact form fields.
- Network and HTTP failures preserve entered values and show localized feedback.
- Destructive operations use accessible confirmation dialogs.
- Workspace deletion requires typing the workspace name and clearly lists affected record counts.
- Deleting the current workspace switches to another authorized workspace when one exists; otherwise the user returns to the empty workspace portfolio.
- Backend errors never partially mutate memberships, task definitions, task assignments, or pivots.

## Localization and Accessibility

All visible text uses semantic translation keys with English, Lithuanian, and Russian parity and English fallback. Dates and numbers use the existing locale and timezone-aware utilities.

Dialogs manage focus, destructive buttons have explicit accessible labels, keyboard navigation works across management sections, color is never the only status indicator, and async controls expose disabled, processing, success, and error states.

## Testing and Verification

Focused Pest coverage includes:

- authenticated portfolio and management-page access;
- accurate counts and current-workspace state;
- owner, admin, member, and outsider permission matrices;
- workspace create, update, duplicate, switch, ownership transfer, and delete;
- member invitation, role update, removal, and sole-owner protection;
- label, tag, status, and priority CRUD;
- ordering, default semantics, completion semantics, archive behavior, usage counts, and delete-with-replacement;
- cross-workspace and mixed-identifier attacks with atomic rollback;
- populated SQLite migration and legacy API compatibility;
- API authentication, abilities, validation, authorization, and response contracts;
- English, Lithuanian, and Russian copy parity.

Verification runs focused tests first, then Pint, Larastan, Pest, frontend tests, Vue type checking, ESLint, Prettier verification, production build, `git diff --check`, and live browser CRUD checks on the Herd URLs. Fresh browser logs must show no errors caused by the feature.

## Delivery Strategy

Implementation is split into coherent phases so each phase can be verified and delivered without mixing the user's existing `docs/progress.md` changes:

1. workspace portfolio CRUD and accurate counts;
2. management page and membership operations;
3. label and tag management plus isolation hardening;
4. configurable statuses and priorities with migration/backfill;
5. task integration and compatibility API;
6. full regression verification, progress record, and delivery.
