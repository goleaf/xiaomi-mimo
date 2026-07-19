# Architecture

## Supported System

Laravel owns routing, session and token authentication, authorization, validation, persistence, scheduling, and Inertia responses. Vue 3 pages use `<script setup lang="ts">`, the Composition API, existing Reka/shadcn-style primitives, Pinia only for shared state, and Wayfinder for typed Laravel routes. SQLite is the only supported relational database.

## Source-Of-Truth Boundaries

### HTTP Presentation

- Page controllers accept authorized, validated input and return Inertia responses or redirects.
- API controllers live under a versioned boundary and return consistent Eloquent API Resources/errors.
- A controller never calls another controller or selects presentation based on `expectsJson()`.
- Route closures are limited to static redirects or protocol metadata; they do not resolve workspaces or execute product queries.
- Nested route bindings are scoped to their parent when the URL expresses containment.

### Domain Writes

- Every domain write uses an authorizing Form Request or a policy-authorized command with no request payload.
- State-changing use cases live in injected action classes.
- Multi-record and aggregate writes validate the complete workspace-scoped record set before a short transaction.
- Completion, archive, recurrence, reorder, ownership, import, and deletion are explicit transitions, not arbitrary attribute arrays.
- Activity is emitted through one documented taxonomy at the successful transaction boundary.

### Domain Reads

- Focused query objects own task index/detail, project index/detail, dashboard, calendar, activity, and notification reads.
- Queries start from an already authorized workspace/user relation, select required columns, eager-load required relationships, calculate counts/aggregates, paginate, and apply deterministic secondary ordering.
- API Resources and model accessors never execute queries. Resources use only already-loaded relations/counts.
- User timezone boundaries are calculated in application code and supplied as canonical query bounds compatible with SQLite.

### Authorization And Isolation

- The workspace role enum remains the role source; policies implement the documented owner/admin/member permission matrix.
- Related UUID existence is insufficient. Project, assignee, parent, labels, tags, reminders, attachments, children, reorder IDs, and bulk IDs must belong to the authorized workspace/aggregate.
- Submitted ID sets are exact: one missing or foreign ID rejects the whole operation.
- Frontend visibility mirrors but never replaces backend authorization.

### Frontend State

- Inertia page props are immutable inputs. Local editable state synchronizes by record identity and resets transient state when context closes/changes.
- URL query parameters are the reproducible source for list view/filter/sort/range state; user preferences supply defaults.
- Async interactions expose processing, duplicate prevention, validation, failure, success where useful, and rollback/restoration.
- Dialogs and drawers use the existing Reka primitives for focus trap, Escape, initial/return focus, accessible naming, keyboard behavior, reduced motion, and responsive layouts.
- Semantic translation keys and centralized locale/timezone formatters own all user-facing copy and formatting.

### External API

- The target prefix is `/api/v1`; unversioned compatibility routes, if retained, call the same controllers/actions and carry explicit deprecation metadata.
- Successful items use `data` plus optional `meta`; collections use `data`, pagination `links`, and pagination `meta`.
- Errors contain a stable machine code, localized safe message, validation fields when applicable, and request identifier.
- Sanctum abilities separate workspace read/write, task read/write, export, and attachment access. Browser session and token behavior are tested independently.

### SQLite And Files

- SQLite configuration is validated once at connection/startup, not before every query.
- Runtime pragmas are environment-configurable only when supported by installed Laravel/PDO versions.
- Corrective migrations must be SQLite-compatible and safe for populated databases.
- The main DB, WAL, and SHM reside on a local SQLite-compatible filesystem.
- Backups use a consistent SQLite backup mechanism, atomic private storage, integrity verification, opaque inventory IDs, and a guarded restore protocol.
- Attachments use private generated storage paths and safe download headers; import/export are bounded, versioned, and workspace-scoped.

## Current Architecture Delta

The current implementation violates several target boundaries: domain queries/controller resolution live in shortcut closures; web/API controllers duplicate behavior; todo index mixes Inertia and JSON; write paths bypass actions or policies; resources/accessors can trigger queries; API responses and token permissions are inconsistent; custom global route resolution competes with Wayfinder; and the live schema lacks intended domain FKs.

The ordered correction path is `docs/implementation-roadmap.md`. Authorization/isolation work must land before route/controller deduplication, and query contracts must land before index removal/addition or broad frontend reconstruction.

## Decision Log

| Decision                                                              | Rationale                                                          | Status              |
| --------------------------------------------------------------------- | ------------------------------------------------------------------ | ------------------- |
| Preserve Laravel 13/Inertia 3/Vue 3/Wayfinder/SQLite                  | Permanent repository contract and installed stack                  | Final               |
| Keep page and API controllers separate but share actions/queries      | Prevent presentation branching without duplicating domain behavior | Target for phase 3  |
| Repair authorization before refactoring routes/controllers            | Avoid reproducing insecure semantics behind cleaner abstractions   | Sequencing decision |
| Derive indexes from final query objects and query plans               | Avoid speculative write-cost increases                             | Sequencing decision |
| Use one translation architecture and English fallback                 | Stable keys and parity across three locales                        | Target for phase 7  |
| Do not remove apparently dead frontend code/dependencies during audit | Reference tracing and feature decisions are not complete           | Audit safeguard     |

No production architecture was changed in prompt 1.
