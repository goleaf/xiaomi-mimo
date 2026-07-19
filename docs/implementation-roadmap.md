# Implementation Roadmap

## Sequencing Principles

1. Security boundaries precede structural refactors.
2. One behavior is proven through tests before duplicate presentations are consolidated.
3. Query contracts precede SQLite index tuning and large frontend changes.
4. Destructive data-transfer operations remain isolated until their threat model and recovery path are testable.
5. Every phase updates `docs/progress.md`, runs focused tests first, then the complete quality matrix, commits only related files, and pushes `main`.

## Phase 2: Workspace Isolation And Permissions

**Priority:** Critical. **Depends on:** audited role requirements and existing enums.

- Implement the owner/admin/member permission matrix and missing policies.
- Fix workspace selection, member removal, final-owner protection, and invitation security foundation.
- Add workspace-aware related-ID validation, scoped binding, exact bulk sets, enum-backed transitions, and dedicated reorder requests/actions.
- Define least-privilege Sanctum abilities and API throttling boundaries.
- Start with cross-workspace attack and role-matrix Pest tests.

**Exit criteria:** every submitted domain ID is authorized within the route workspace; mixed sets fail atomically; final ownership is protected; missing token abilities are denied.

## Phase 3: Routes, Controllers, Actions, Queries, And API v1

**Priority:** High. **Depends on:** phase 2 policies and invariants.

- Replace query-bearing/controller-calling shortcut closures with focused page controllers.
- Separate Inertia from API presentation and expose `/api/v1` with a consistent resource/error contract.
- Inject shared actions; add focused task/project/dashboard/activity/calendar/notification query objects.
- Normalize names and migrate custom/hardcoded route calls to Wayfinder.
- Decide and test any temporary unversioned API deprecation path.

**Exit criteria:** no controller-to-controller calls, no `expectsJson()` presentation branching, one domain implementation per operation, typed Wayfinder build passes.

## Phase 4: SQLite Schema And Query Performance

**Priority:** Critical/High. **Depends on:** final scoped query shapes from phase 3.

- Add populated-data preflight and SQLite-compatible corrective FKs/UUID morph migrations.
- Add hierarchy/ownership/value constraints where feasible; preserve existing data safely.
- Make supported pragmas configurable, validate DB paths, and add internal health diagnostics.
- Remove accessor queries and use query-level counts/aggregates.
- Use `EXPLAIN QUERY PLAN` and representative data before changing indexes.
- Add fresh, seeded, populated-upgrade, constraint, query-count, integrity, and busy-classification tests.

**Exit criteria:** live and fresh schemas match documented invariants; runtime pragmas are verified; major page query budgets are enforced.

## Phase 5: Task Index, Filters, Board, And Bulk UX

**Priority:** High. **Depends on:** phases 2-4 task APIs/queries.

- Make task index a coordinator around filter, list, board, bulk, dialog/drawer, pagination, and state components.
- Define URL-backed validated filters/sorts and user-preference-backed view mode.
- Choose one accessible drag/drop approach; remove unused DnD dependencies only if proven unnecessary.
- Implement complete authorized bulk controls and resilient async states.
- Verify desktop/mobile, touch, keyboard, rollback, pagination, and stale-request behavior.

## Phase 6: Task Detail And Collaboration

**Priority:** High. **Depends on:** phase 2 child policies and phase 3 detail query contract.

- Split task detail by feature and synchronize state by task identity.
- Isolate checklist state per checklist and add full checklist/item lifecycle/reorder.
- Complete comment moderation/pagination, scoped label/tag management, reminders/recurrence editing, and secure attachment UX.
- Rebuild the detail drawer on accessible Reka primitives.

## Phase 7: Localization And User Formatting

**Priority:** High. **Depends on:** stable route/resource/feature vocabulary.

- Implement semantic-key English/Lithuanian/Russian translations with parity/fallback checks.
- Replace frontend/backend hardcoded user copy and enum string formatting.
- Centralize locale/timezone/date/time/number/relative formatters and persist preferences/session locale.
- Set document language/direction and test validation/page titles/timezone boundaries.

## Phase 8: Recurrence, Reminders, And Notifications

**Priority:** High. **Depends on:** phase 4 schema/retry and phase 7 timezone rules.

- Define canonical bounded recurrence and idempotent occurrence identity.
- Add short-transaction catch-up generation with relationship-copy rules and activity.
- Add reminder claim/delivery/failure/cancel states and duplicate protection.
- Complete user-scoped paginated notification UX; be explicit about browser-only delivery limits.

## Phase 9: Import, Export, Attachments, Backup, And Restore

**Priority:** Critical. **Depends on:** phase 4 SQLite/storage foundation and phase 7 localized errors.

- Add versioned/bounded import preview and validated transactional execution.
- Stream safe JSON/CSV/Markdown exports with formula protection.
- Finish private attachment storage/type/header/path controls.
- Replace file copying with a WAL-consistent, verified, atomic backup strategy.
- Implement owner/password-confirmed restore through an opaque inventory, exclusive lock, maintenance guard, and rollback plan.

## Phase 10: Dashboard, Projects, Workspaces, Calendar, Activity, Navigation

**Priority:** Medium/High. **Depends on:** phases 2-8 shared boundaries.

- Complete aggregate/deferred dashboard data and accessible chart equivalents.
- Complete project lifecycle/transactional duplication, workspace switch/roles/ownership/leave/delete, calendar ranges/views, and activity taxonomy/filter/privacy.
- Reset/cancel client state on workspace switch and use Wayfinder navigation on desktop/mobile.

## Phase 11: Production Readiness Audit

**Priority:** Release gate. **Depends on:** all prior phases.

- Remove only traced dead code/dependencies/helpers/artifacts; fix the entire quality baseline.
- Verify all page states, responsive layouts, keyboard/focus/contrast/reduced motion, translations, security attacks, SQLite integrity/pragmas/indexes/query budgets, migrations, and isolated backup/restore.
- Make development seed data repeatable and comprehensive without real credentials.
- Reconcile all documentation, changelog, deployment/scheduler/SQLite limits, and final progress evidence.

## Risk Register

| Risk                                  | Interim control                                                                            | Owning phase |
| ------------------------------------- | ------------------------------------------------------------------------------------------ | ------------ |
| Cross-workspace writes                | Do not expose production use until phase 2 attack tests pass                               | 2            |
| Backup/restore corruption or takeover | Treat current HTTP backup/restore as unsafe for production                                 | 9            |
| Missing live foreign keys             | Rely on strict application scoping only as a temporary measure                             | 2 and 4      |
| Unrestricted API tokens               | Limit API exposure until abilities are enforced                                            | 2-3          |
| Known-password invitations            | Do not use automated account creation for real invitations                                 | 2/10         |
| Frontend type/lint debt               | Production build passes, but feature phases must reduce and phase 11 must clear all checks | 3-11         |
