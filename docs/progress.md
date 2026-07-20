# Progress

## Phase 0: Repository Contract And Documentation Baseline

### Completed Work

- Read the complete `AGENTS.md`, manifests, route files, backend domain layers, migrations/indexes, Vue/TypeScript application layers, and tests.
- Inspected Git state and recent history, current Artisan routes, installed package versions, the live SQLite schema/indexes, and runtime SQLite diagnostics.
- Searched version-specific Laravel 13, Inertia 3, Fortify, Sanctum 4, and Wayfinder documentation through Laravel Boost.
- Established the permanent requirements and documentation baseline without changing product behavior.

### Changed Files

Documentation files under `docs/`, `README.md`, `CHANGELOG.md`, and the project-specific section appended to `AGENTS.md`.

### Migrations And Packages

No migrations changed. No packages were added, removed, or upgraded.

### Decisions

- Preserve Laravel/Inertia/Vue/Wayfinder and SQLite-only architecture.
- Treat workspace isolation as the primary security boundary.
- Separate page, API, action, query, policy, resource, and Vue feature responsibilities over later phases.
- Record current risks as unresolved until complete flows and tests prove corrections.

### Verification

- `vendor/bin/pint --dirty --format agent`: passed; no PHP production files were changed.
- `php artisan test --compact`: passed, 111 tests and 329 assertions.
- `npm run build`: passed; Vite generated the production bundle and Wayfinder types.
- `vendor/bin/phpstan analyse --no-progress`: failed with 377 pre-existing application type errors.
- `npm run types:check`: failed with 15 pre-existing TypeScript/Vue errors.
- `npm run lint:check`: failed with 88 pre-existing ESLint errors.
- `npm run format:check`: failed; 19 pre-existing resource files require formatting.
- `git diff --check`: passed.

The failed checks identify baseline production-code debt. They were not modified in this documentation-only phase and are inputs to the audit and implementation roadmap.

### Git Delivery

Commit and push results are recorded after delivery.

### Known Limitations

The comprehensive finding-by-finding audit belongs to prompt 1. Current production behavior remains unchanged, including known authorization, routing, response, frontend, SQLite, import/export, and backup risks.

### Next Phase

Prompt 1: complete evidence-based backend, domain, database, frontend, security, and test audit with classified findings and implementation roadmap.

## Phase 1: Repository Audit And Source-Of-Truth Architecture Plan

### Completed Work

- Re-read `AGENTS.md`, every phase-0 requirements document, installed manifests, routes, controllers, actions, services, Form Requests, resources, policies, models, enums, commands, providers, notifications, middleware, migrations, factories, seeders, Vue pages/layouts/components/composables/stores/types, and all tests.
- Inspected the Artisan route table and recorded every routed controller method with middleware, request, ability/scope, action/query, response, consumers, duplication, and test gaps.
- Inspected the live SQLite schema and indexes through Laravel tooling, compared it with migrations, and verified runtime pragmas/integrity.
- Searched installed-version Laravel, Inertia, Fortify, Sanctum, and Wayfinder documentation through Laravel Boost before documentation edits.
- Classified backend, database, frontend, security, and testing findings as critical, high, medium, low, or informational with evidence, impact, correction, dependencies, tests, and owning phase.
- Produced an ordered implementation roadmap without changing production behavior.

### Changed Files

- `docs/current-state.md`
- `docs/audit/backend.md`
- `docs/audit/frontend.md`
- `docs/audit/database.md`
- `docs/audit/security.md`
- `docs/audit/testing.md`
- `docs/architecture.md`
- `docs/implementation-roadmap.md`
- `docs/progress.md`

### Migrations And Packages

No migration was added or changed. No Composer or npm package was added, removed, or upgraded.

### Architectural Decisions

- Repair workspace isolation, role permissions, and exact submitted-ID validation before consolidating controllers or changing route contracts.
- Keep page and API controllers as separate presentation adapters while sharing actions and query objects.
- Introduce API v1 and a single resource/error contract in phase 3; decide deprecation aliases from an explicit compatibility requirement.
- Establish final scoped query objects before changing SQLite indexes.
- Correct live UUID foreign/morph schema defects through populated-data-safe SQLite migrations rather than rewriting deployed migrations.
- Use official Wayfinder generation as the target and remove the custom global route helper only after all consumers are migrated and typed.

### Security Decisions

- Treat bulk task operations, unguarded child mutations, known-password invitation creation, and HTTP backup/restore as critical risks.
- Treat the route workspace as the tenant authority; globally existing UUIDs never prove authorization.
- Require explicit Sanctum abilities and separate API token behavior from web session behavior.
- Require owner authorization, recent password confirmation, opaque server-side inventory, WAL consistency, integrity checks, locking, and rollback for restore.

### Verification

- Focused `php artisan test --compact tests/Feature/WorkspaceTest.php tests/Feature/TodoTest.php`: passed, 14 tests and 29 assertions.
- `vendor/bin/pint --dirty --format agent`: passed; no PHP files required formatting.
- `php artisan test --compact`: passed, 111 tests and 329 assertions.
- `npm run build`: passed; Vite generated the production bundle and Wayfinder types. It emitted only the existing optional `fontaine` optimization notice.
- `vendor/bin/phpstan analyse --no-progress`: failed with the same 377 pre-existing application type errors.
- `npm run types:check`: failed with the same 15 pre-existing TypeScript/Vue errors.
- `npm run lint:check`: failed with the same 88 pre-existing ESLint errors.
- `npm run format:check`: failed; the same 19 pre-existing files under `resources/` require formatting.
- Prompt-1 documentation-only Prettier check: passed for all eight audited/updated architecture files.
- `git diff --check`: passed.

The production-code failures were not changed in this audit-only phase. No test was added solely to assert documentation content; existing focused and complete suites were used to verify unchanged behavior.

### Known Limitations

- No critical/high finding is fixed yet.
- The live domain schema has no foreign keys despite migration intent; UUID morph/foreign columns are inconsistent in passkeys, notifications, and Sanctum tokens.
- The current API remains unversioned and unrestricted by token abilities.
- The frontend quality gates remain red even though its production build succeeds.
- The untracked/staged `.mimocode/plans/1784461861035-kind-garden.md` planning artifact is unrelated to this phase and was deliberately not modified or included in the phase commit.

### Next Phase

Prompt 2: implement and test the critical/high workspace isolation, role permission, nested validation, bulk/reorder, scoped binding, and Sanctum ability corrections.

## UI Repair: Shared Responsive Sidebar

### Status

Completed.

### Completed Work

- Added an authenticated edit mode to the direct task detail page for title, description, status, priority, and due date.
- Submitted edits through the generated Wayfinder controller action and Inertia `useForm`, with server validation errors, processing state, cancel/reset behavior, refreshed props, and a success toast.
- Corrected the web update response to redirect for Inertia/browser requests while preserving JSON responses for JSON consumers.
- Corrected the update action so nullable optional fields can actually be cleared instead of being discarded before persistence.
- Added semantic task editing translations for English, Lithuanian, and Russian.
- Added focused Pest regressions for the redirect response, nullable field clearing, and localized edit-page props.

### Changed Files

- `app/Actions/UpdateTodo.php`
- `app/Http/Controllers/TodoController.php`
- `resources/js/pages/tasks/Show.vue`
- `lang/en/tasks.php`
- `lang/lt/tasks.php`
- `lang/ru/tasks.php`
- `tests/Feature/TodoTest.php`
- `docs/progress.md`

### Scope And Decisions

- Restore the starter-kit sidebar primitives that were replaced by a static `aside`, so the left navigation, desktop collapse state, keyboard shortcut, and mobile drawer share one state model.
- Keep the existing Inertia application layout as the single shell for authenticated pages instead of adding page-specific menus.
- Supply workspace and active-project navigation data through shared Inertia props, with the selected workspace resolved from the authenticated session.
- Use generated Wayfinder route functions for sidebar navigation and Inertia's standalone HTTP client for the JSON workspace-switch endpoint.
- Add English, Lithuanian, and Russian semantic navigation translations with English fallback.

### Planned Files

- Shared Inertia middleware and current-workspace resolution call sites.
- Sidebar, workspace switcher, navigation, logo/header, and shared TypeScript types.
- Navigation translation files and focused Pest coverage.

### Migrations And Packages

No migration or package change is planned.

### Verification Plan

- Focused Pest tests for shared sidebar props, locale labels, and session-aware workspace switching.
- Pint, Larastan, Vue type checking, ESLint, Prettier verification, production build, and `git diff --check`.

### Git Delivery

Commit and push results will be recorded after verification. The unrelated staged `.mimocode/plans/1784461861035-kind-garden.md` file will remain untouched and excluded.

## NativePHP Mobile 3 Quick-Start Integration

### Status

Completed.

### Completed Work

- Installed NativePHP Mobile 3 and initialized both supported mobile platforms with the embedded PHP 8.4 runtime.
- Configured `com.goleaf.xiaomimimo` as the Android application ID and iOS bundle identifier, `/` as the local start URL, and the persistent runtime mode.
- Integrated the NativePHP Vite plugin and hot-file path while retaining the existing Laravel, Inertia, Vue, Pinia, Wayfinder, and browser development workflow.
- Configured Inertia 3 to use its Axios HTTP adapter so page visits execute against the bundled on-device Laravel runtime.
- Added full-screen safe-area handling for the Android and iOS WebView.
- Kept SQLite as the only database and configured attachment upload, URL, download, and deletion behavior to use NativePHP's writable `mobile_public` disk in packaged apps and the existing public disk in browser development.
- Expanded bundle-time environment cleanup so development credentials and application secrets are not shipped in the mobile bundle.
- Added focused Pest coverage for the NativePHP command/configuration contract and on-device attachment lifecycle.
- Added no remote API, synchronization service, client/server split, Redis, or external database requirement.

### Changed Files

- `.env.example`
- `composer.json`, `composer.lock`
- `package.json`, `package-lock.json`
- `config/nativephp.php`, `config/filesystems.php`
- `native`, `nativephp.lock`
- `vite.config.ts`, `resources/js/app.ts`, `resources/views/app.blade.php`
- Attachment action, controller, resource, and model files
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

- Added `nativephp/mobile` 3.3.6 and its locked Composer dependencies.
- Added direct runtime dependencies on `@inertiajs/core` 3.6.1 and Axios 1.18.1 for the Inertia 3 mobile HTTP adapter.
- NativePHP installed embedded PHP 8.4.23 with ICU disabled.
- No application migration was added or changed. SQLite remains the only relational database.

### Verification

- `php artisan native:install both --no-interaction`: passed; Android and iOS projects and embedded PHP were installed.
- `php artisan native:plugin:list`: passed; no optional NativePHP plugins are required by this quick-start phase.
- `php artisan test --compact tests/Feature/NativePhpMobileTest.php`: passed, 2 tests and 18 assertions.
- Scoped PHPStan analysis for all NativePHP and attachment integration PHP files: passed with zero errors.
- `php artisan test --compact`: passed at the integration checkpoint with 116 tests and 392 assertions. A final run after concurrent settings/member tests and implementation appeared ran 128 tests, with 120 passing and 8 unrelated failures in that in-progress work; the NativePHP suite still passes independently.
- `vendor/bin/pint --dirty --format agent`: passed.
- Targeted ESLint for `resources/js/app.ts`: passed.
- Targeted Prettier for the NativePHP TypeScript and package files: passed.
- `npm run build`: passed; the standard production application bundle includes the Axios and NativePHP integration.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev`: passed with no known dependency vulnerabilities.
- `git diff --check`: passed.
- Full PHPStan remains at 365 pre-existing errors, improved from the documented 377-error baseline; NativePHP-touched PHP files are clean.
- Full Vue type checking remains at 11 errors and full ESLint remains at 85 errors in pre-existing or concurrently edited non-mobile files; the NativePHP application entrypoint is clean.
- Full resource Prettier verification remains red in 14 unrelated resource files.

### Git Delivery

Implementation commit `264d8d6` (`feat: integrate NativePHP Mobile 3`) was pushed successfully to `origin/main`. This phase-related progress update will be committed and pushed separately; existing sidebar, settings, task, translation, and `.mimocode` changes remain outside both commits.

### Known Limitations

- NativePHP's generated Android and iOS source projects remain locally reproducible and intentionally ignored by the generated `nativephp/.gitignore`; the tracked installer configuration, launcher, and lock file are the source of truth.
- Platform-mode Vite builds and device launch/watch commands were not executed automatically, as required by the installed NativePHP development guidance.
- This Intel Mac does not satisfy NativePHP's Apple-silicon iOS development requirement and does not have full Xcode, CocoaPods, Android Studio, or an Android SDK available. Device compilation must be completed on a supported workstation.
- No Apple development team or product-specific icon/splash assets were supplied, so code signing remains unset and the NativePHP defaults remain in use.

## UI Repair: Complete Profile Settings

### Status

Completed.

### Completed Work

- Added authenticated avatar upload, preview, progress, replacement, display, and removal with strict JPG/PNG/WebP, extension, MIME, 2 MB, and 4096-pixel validation.
- Stored generated avatar filenames on a dedicated private disk, deleted replaced and removed files with explicit failure handling and database rollback, cleaned the avatar during account deletion, and served the current user's image with private no-store and nosniff headers.
- Rebuilt the profile page around profile photo and personal information cards, removed the duplicate password form in favor of the Security page, and restored email-verification resend feedback.
- Replaced the broken confirmation-only account deletion button with the existing accessible password-confirming dialog.
- Added generated Wayfinder form routing, responsive Tailwind CSS 4 behavior, avatar fallbacks, duplicate-submit prevention, localized success/error states, and semantic English, Lithuanian, and Russian profile translations with English fallback.
- Verified the live page on desktop and a 390-pixel viewport; avatar changes propagate to the account menu and the page creates no document-level horizontal overflow.

### Changed Files

- `app/Actions/DeleteProfileAvatar.php`
- `app/Actions/UpdateProfileAvatar.php`
- `app/Http/Controllers/Settings/ProfileController.php`
- `app/Http/Requests/Settings/ProfileAvatarDeleteRequest.php`
- `app/Http/Requests/Settings/ProfileAvatarUpdateRequest.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `config/filesystems.php`
- `database/migrations/2026_07_19_134932_add_avatar_path_to_users_table.php`
- `routes/settings.php`
- `resources/js/pages/settings/Profile.vue`
- `resources/js/components/DeleteUser.vue`
- `resources/js/layouts/settings/Layout.vue`
- `lang/en/settings.php`, `lang/lt/settings.php`, and `lang/ru/settings.php`
- `tests/Feature/Settings/ProfileUpdateTest.php`
- `docs/progress.md`

### Migrations And Packages

- Added and applied one SQLite-compatible nullable `users.avatar_path` column.
- No package was added, removed, or upgraded.

### Verification

- `php artisan test --compact tests/Feature/Settings/ProfileUpdateTest.php`: passed, 19 tests and 145 assertions, including simulated storage deletion failures and recoverable account-avatar staging.
- `php artisan test --compact`: passed, 148 tests and 659 assertions.
- `vendor/bin/pint --dirty --format agent`: passed.
- Scoped Larastan for both avatar actions, the controller, and both requests: passed with zero errors.
- `npm run build`: passed; Wayfinder regenerated and Vite emitted only the existing optional `fontaine` notice.
- Targeted ESLint and Prettier checks for the three changed Vue files: passed.
- `git diff --check`: passed.
- Authenticated Chromium verified upload (`302`), removal (`303`), live avatar/fallback propagation, success toasts, password-confirming deletion dialog, a cancel action that sends no DELETE request, zero page console errors, and a 390-pixel document width without horizontal overflow.
- Live private-storage verification confirmed the uploaded file exists only under `storage/app/private/profile-avatars`, the public `/storage/...` path is denied, and the authenticated endpoint returns `private, no-store` plus `nosniff` headers.
- Full Larastan remains red with 364 existing errors outside this profile flow.
- Full Vue type checking remains red with nine existing errors outside the changed profile files.
- Full ESLint remains red with 72 existing errors outside the changed profile files.
- Full Prettier verification remains red on 13 existing files; all changed profile Vue files pass their targeted check.
- The mandatory post-implementation code review found no critical issues; its private-storage, explicit deletion-failure, cancel-button, and localized-breadcrumb findings are resolved in the follow-up hardening change.

### Known Limitations

- Existing settings-navigation labels outside the profile content remain part of the broader localization phase.
- Concurrent sidebar work owns the shared Inertia middleware and responsive settings-layout base; this repair adds only avatar and profile-specific integration to those files.

### Git Delivery

- Commit `c23f582` (`feat: complete profile settings`) contains only the 14 profile code, migration, translation, layout, and test files.
- Push to `origin main` succeeded (`dc5ff2e..c23f582`).
- Follow-up commit `1bc66f1` (`fix: harden profile avatar lifecycle`) contains only the eight reviewed profile/config/test files and was pushed successfully to `origin/main` (`c23f582..1bc66f1`).
- Final review follow-up commit `c04d7a3` (`fix: make profile deletion recoverable`) stages account avatars before deletion, restores them on database failure, localizes the settings navigation landmark, and was pushed successfully to `origin/main`.
- The shared Inertia avatar prop remains in the separately staged sidebar batch; the mixed `docs/progress.md` index/worktree state also remains excluded from the isolated profile commit.

## Task Detail Editing Repair

### Status

In progress.

### Scope And Decisions

- Restore editing on the direct `/tasks/{todo}` Inertia page for the title, description, status, priority, and due date.
- Keep `UpdateTodoRequest`, `TodoPolicy`, and `UpdateTodo` as the validation, authorization, and state-change boundaries.
- Return a redirect for Inertia form submissions while preserving the existing JSON response for JSON clients.
- Use generated Wayfinder controller actions, existing Vue UI primitives, and English, Lithuanian, and Russian semantic translations.

### Migrations And Packages

No migration or package change is planned.

### Verification

- The redirect and nullable-field regressions failed first for the expected `200` response and retained nullable values, then passed after their respective fixes.
- `php artisan test --compact tests/Feature/TodoTest.php`: passed, 12 tests and 37 assertions.
- `php artisan test --compact`: passed, 130 tests and 512 assertions on the final sequential run.
- Scoped Pint for the six changed PHP files: passed.
- Targeted ESLint and Prettier checks for `resources/js/pages/tasks/Show.vue`: passed.
- `npm run build`: passed; Wayfinder generated types and Vite emitted only the existing optional `fontaine` notice.
- `git diff --check` for the repair files: passed.
- Authenticated Chromium verification on `/tasks/019f7a48-e93e-700c-a984-26341f7ddf06`: edit controls rendered, both update and restore returned `303`, the refreshed title rendered, and no console error occurred. The task title was restored after verification.
- Full Larastan remains red with 364 existing application errors; the scoped controller/action run reports four existing typing/query errors.
- Full Vue type checking remains red with nine existing errors outside `tasks/Show.vue`; the changed page has no type error.
- Full ESLint remains red with 72 existing errors outside `tasks/Show.vue`.
- Full Prettier verification remains red on 15 existing files; the changed page passes its targeted check.

### Known Limitations

- Existing hardcoded task-detail display copy and locale-aware date formatting remain broader localization debt; all copy introduced by this repair is translated.
- Concurrent profile, members, preferences, sidebar, NativePHP, package, and planning work remains outside this repair.

### Git Delivery

- Commit `22ab435` (`fix: restore task detail editing`) contains only the seven task-repair code, translation, and test files.
- Push to `origin main` succeeded (`8f150c2..22ab435`).
- The mixed pre-existing `docs/progress.md` index/worktree state remains excluded from the isolated repair commit.

## Settings UI: Unified Preferences And Appearance

### Status

Completed.

### Completed Work

- Consolidated the functional light, dark, and system appearance control into the Preferences page.
- Removed the duplicate database-backed theme select from the preference form so appearance continues to use the existing cookie and local-storage behavior.
- Removed the duplicate Appearance settings navigation item and superseded standalone Vue page.
- Preserved `/settings/appearance` as an authenticated GET compatibility redirect to the canonical `/settings/preferences` route.
- Replaced the affected hardcoded frontend URLs with generated Wayfinder route functions and made the locale controls responsive on narrow screens.
- Added focused Pest coverage for the canonical Inertia page, compatibility redirect, authentication boundary, and shared appearance control.

### Changed Files

- `routes/settings.php`
- `resources/js/layouts/settings/Layout.vue`
- `resources/js/pages/settings/Preferences.vue`
- `resources/js/pages/settings/Appearance.vue` (removed)
- `tests/Feature/Settings/PreferencesTest.php`
- `docs/progress.md`

### Scope And Decisions

- Make `/settings/preferences` the canonical page for account preferences and appearance controls.
- Preserve `/settings/appearance` as an authenticated compatibility redirect to the canonical preferences route.
- Remove the duplicate Appearance navigation entry and keep the functional cookie/local-storage appearance control on the unified page.
- Use generated Wayfinder route functions for settings navigation and preference submission.

### Planned Files

- Settings routes, layout navigation, and the unified Preferences Vue page.
- The superseded standalone Appearance Vue page.
- Focused Pest coverage for the canonical page, compatibility redirect, and authentication boundary.

### Migrations And Packages

No migration or package was added, removed, or changed.

### Verification

- Focused `php artisan test --compact tests/Feature/Settings/PreferencesTest.php`: passed, 5 tests and 18 assertions after the expected RED run failed on the old separate-page behavior.
- Scoped `vendor/bin/phpstan analyse --no-progress routes/settings.php`: passed with zero errors.
- `vendor/bin/pint --dirty --format agent routes/settings.php tests/Feature/Settings/PreferencesTest.php`: passed.
- Targeted ESLint and Prettier checks for the two modified Vue files: passed.
- `npm run build`: passed; Wayfinder regenerated route and form definitions, and Vite emitted only the existing optional `fontaine` notice.
- `git diff --check`: passed.
- Full Vue type checking remains red on 11 unrelated files; neither modified Vue file reports an error.
- Full ESLint remains red with 75 unrelated errors; both modified Vue files pass targeted ESLint.
- Full Prettier verification remains red on 13 unrelated files; both modified Vue files pass targeted Prettier.
- The complete Pest run passed 123 of 129 tests and failed only the six concurrently added, unrelated `SettingsMembersTest` cases; all unified preferences tests pass.

### Known Limitations

- Existing settings copy remains English-only as part of the repository's pre-existing localization debt; this consolidation reuses rather than expands that copy.
- Concurrent profile, members, task, sidebar, NativePHP, attachment, package, and planning work remains outside this phase.

### Git Delivery

Implementation commit `d74c709` (`feat: unify settings preferences and appearance`) was pushed successfully to `origin/main`. This phase-related progress update remains uncommitted because `docs/progress.md` also contains staged and unstaged work from concurrent phases; committing the whole file would include unrelated changes.

## Settings UI: Workspace Members Roster Repair

### Status

Completed.

### Completed Work

- Replaced the crashing nested `member.user` assumption with an explicit, workspace-scoped roster contract containing only the fields required by the page.
- Rebuilt the page as a responsive roster with member identity, localized role context, search, management summary, invitation form, read-only state, and an accessible removal dialog.
- Wired invite and removal submissions to generated Wayfinder controller actions and returned browser-compatible redirects so Inertia refreshes the roster after mutations.
- Authorized removals through the workspace policy, rejected foreign member identifiers, and prevented removal of the workspace owner or current user.
- Added semantic English, Lithuanian, and Russian members copy.
- Made the shared settings navigation responsive so the roster remains usable at mobile widths.
- Added focused Pest coverage for roster shape, selected-workspace isolation, permissions, localization, invitation, removal, and owner protection.

### Changed Files

- `app/Http/Controllers/Settings/MembersController.php`
- `app/Http/Controllers/WorkspaceController.php`
- `resources/js/pages/settings/Members.vue`
- `resources/js/layouts/settings/Layout.vue`
- `lang/en/members.php`, `lang/lt/members.php`, `lang/ru/members.php`
- `tests/Feature/SettingsMembersTest.php`
- `docs/progress.md`

### Scope And Decisions

- Repair the Inertia member payload mismatch that renders the count but crashes before displaying the roster.
- Replace the members page with a responsive, accessible workspace roster and invitation flow using the existing Vue, Tailwind CSS 4, Reka UI, and Wayfinder stack.
- Keep every member query and mutation scoped to the selected authorized workspace, expose only the fields required by the page, and prevent removal of the owner or current user.
- Add semantic English, Lithuanian, and Russian copy with English fallback.
- Preserve all unrelated sidebar, NativePHP, attachment, task, profile, preferences, package, and planning changes already in the worktree.

### Migrations And Packages

No migration or package was added, removed, or changed.

### Verification

- The six focused regressions failed first for the missing roster props, locale copy, redirect contract, and undefined removal request, then passed after implementation: 6 tests and 78 assertions.
- `php artisan test --compact`: passed, 130 tests and 512 assertions.
- Scoped Pint and Larastan for the changed PHP files: passed with zero errors.
- Targeted ESLint and Prettier verification for the two changed Vue files: passed.
- `npm run build`: passed; Vite generated the production bundle and emitted only the existing optional `fontaine` notice.
- `git diff --check`: passed.
- Authenticated Chromium verification at 1440px and 390px showed Demo User, Alice Chen, and Bob Smith; search and the removal confirmation worked; the mobile document had no horizontal overflow; no page or console error occurred.
- Full Vue type checking remains red with 9 existing errors outside the members files. Full Larastan remains red with 364 existing errors, full ESLint with 72 existing errors, and full Prettier verification with 15 existing files; all phase-scoped checks pass.

### Known Limitations

- The wider repository quality-gate baseline remains red as recorded above; this phase did not modify those unrelated files.
- Concurrent profile, preferences, task, sidebar, NativePHP, attachment, package, route, and planning work remains outside this phase.

### Git Delivery

Implementation commit `81a40ce` (`fix: rebuild workspace members roster`) was pushed successfully to `origin/main`. The phase-related progress update remains uncommitted because `docs/progress.md` also contains staged and unstaged work from concurrent phases; committing the whole file would include unrelated changes.

## NativePHP Mobile 3 Upgrade Guide Reconciliation

### Status

Completed.

### Completed Work

- Reconciled the installed NativePHP Mobile 3.3 runtime with every applicable step in the official v3 upgrade guide.
- Replaced the broad `^3.3` dependency constraint with the guide-aligned `~3.3.0` patch line; Composer confirmed 3.3.6 is current within that line.
- Published `App\Providers\NativeServiceProvider` as the explicit security allow-list for third-party native plugin code.
- Added the documented Android compile, minimum, and target SDK configuration points with defaults of 36, 33, and 36.
- Confirmed the repository has no legacy `nativephp.composer.sh` repository or project-level Composer authentication file.
- Rebuilt both generated native platform shells with `native:install both --force`; embedded PHP remains 8.4.23 with optional ICU disabled.
- Validated plugin discovery with the v3 commands; no optional native plugins are installed or registered.
- Preserved the fully on-device Laravel, Inertia, Vue, and SQLite architecture with no remote client/server integration.

### Changed Files

- `composer.json`, `composer.lock`
- `config/nativephp.php`
- `app/Providers/NativeServiceProvider.php`
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

No application migration or new package was added. NativePHP remains at 3.3.6 with a patch-line Composer constraint.

### Verification

- The focused upgrade test failed first on the old `^3.3` constraint, then `php artisan test --compact tests/Feature/NativePhpMobileTest.php` passed with 3 tests and 27 assertions.
- Scoped PHPStan for the NativePHP provider and configuration passed with zero errors.
- `php artisan native:install both --force --no-interaction`: passed and regenerated both native shells and PHP binaries.
- `php artisan native:plugin:validate --no-interaction` and `php artisan native:plugin:list --all`: passed; no plugins are installed.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev`: passed with no known dependency vulnerabilities.
- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact`: passed, 139 tests and 597 assertions.
- `npm run build`: passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` for this upgrade passed.
- Full PHPStan remains red with 364 existing application errors; the NativePHP upgrade files are clean.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier with 13 existing files; this backend/configuration-only upgrade introduced none of them.

### Git Delivery

Commit `8e120a7` (`chore: reconcile NativePHP 3 upgrade guide`) was pushed successfully to `origin/main`. All concurrent sidebar, settings, task, profile, translation, and planning changes remained outside the phase commit.

### Known Limitations

- ICU remains disabled to avoid the documented mobile binary size increase; it can be enabled later with `--with-icu` if the application requires PHP `intl` on-device.
- Platform-mode frontend builds and simulator/emulator launch commands were not auto-run, per the installed NativePHP project guidance.
- Local iOS compilation remains unavailable on this Intel Mac; CocoaPods is now installed, but full Xcode and Apple silicon are still unavailable. The Android toolchain is configured in the following environment-setup phase.

## NativePHP Mobile 3 Environment Setup

### Status

Completed.

### Completed Work

- Installed Android Studio 2026.1.2.10 and CocoaPods 1.17.0 through Homebrew without trusting or changing unrelated third-party taps.
- Accepted the Android SDK licenses and installed API 36, Build Tools 36.0.0, Platform Tools 37.0.0, the current Intel Android emulator, and the Google APIs API 36 x86_64 system image.
- Created the valid `NativePHP_API_36` Pixel 6 AVD while preserving the existing AVD definitions.
- Pinned normal login shells to Temurin JDK 17 and the local Android SDK through `~/.zprofile`; NativePHP's login-shell diagnostics now detect Java 17, Android Studio, Gradle 8.13, and CocoaPods.
- Bound the ignored local `.env` to this workstation's JDK and SDK paths and exposed the portable Android environment contract in `.env.example`.
- Cast environment-overridden Android SDK levels to integers at the configuration boundary.
- Restored every Homebrew formula removed by Homebrew's automatic post-install cleanup and verified both the existing Homebrew PHP and the normal Laravel Herd PHP paths remain executable.
- Preserved the fully on-device Laravel, Inertia, Vue, and SQLite architecture with no remote client/server integration.

### Changed Files

- `.env.example`
- `config/nativephp.php`
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`
- Ignored/local workstation configuration: `.env`, `~/.zprofile`, Homebrew packages, Android SDK packages, and `~/.android/avd/NativePHP_API_36.avd`.

### Migrations And Packages

No application migration or Composer/npm package was added, removed, or upgraded. All installed packages are workstation-only development dependencies.

### Verification

- The focused environment-contract test failed first because `.env.example` lacked the Android keys, then exposed string SDK levels after local environment wiring; after implementation, `php artisan test --compact tests/Feature/NativePhpMobileTest.php` passed with 4 tests and 32 assertions.
- `zsh -lic 'php artisan native:debug --json --no-interaction'`: passed with NativePHP 3.3.6, PHP 8.4.16, embedded PHP 8.4.23, Android Studio 2026.1.2, Gradle 8.13, Java 17.0.16, CocoaPods 1.17.0, and no optional native plugins.
- SDK inspection confirmed Build Tools 36.0.0, Platform Tools 37.0.0, API 36, and the API 36 x86_64 system image; AVD inspection confirmed `NativePHP_API_36` and Hypervisor.Framework acceleration.
- `adb devices`: passed with an empty device list; no physical Android device is currently connected.
- Scoped Pint and Larastan for the NativePHP files passed with zero errors.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev`: passed with no known dependency vulnerabilities.
- `php artisan test --compact`: passed, 146 tests and 644 assertions.
- `npm run build`: passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.
- Full Larastan remains red with 364 existing application errors; the NativePHP configuration is clean.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier verification with 13 existing files; this configuration-only phase introduced none of them.

### Known Limitations

- iOS builds cannot run on this workstation because NativePHP Mobile 3 requires Apple silicon and full Xcode; this Mac is Intel x86_64 and only has Command Line Tools. CocoaPods alone cannot remove that platform limitation.
- No physical Android device is connected. The API 36 AVD is configured but was not launched.
- Native platform build, run, emulator-launch, and watch commands were not auto-run, per the installed NativePHP project guidance.

### Git Delivery

- Commit `8adf61d` (`chore: configure NativePHP mobile environment`) contains only the NativePHP environment contract, configuration, test, and phase progress files.
- Push to `origin main` succeeded (`1bc66f1..8adf61d`).
- Unrelated staged sidebar, navigation, task, project, export, planning, and shared model/middleware work remains excluded and preserved.

## NativePHP Mobile 3 Installation Guide Reconciliation

### Status

Completed.

### Completed Work

- Confirmed `nativephp/mobile` 3.3.6 is installed on the existing `~3.3.0` patch line and the installer command is registered.
- Confirmed the reverse-DNS application ID `com.goleaf.xiaomimimo`, debug version, and build code are declared in both local and example environments before installation.
- Cast `NATIVEPHP_APP_VERSION_CODE` to an integer at the configuration boundary so `.env` overrides preserve the numeric build-code contract.
- Added `/nativephp` to the root `.gitignore`, making the generated Android/iOS platform shell explicitly ephemeral as recommended by the installation guide.
- Ran `native:install both --force --no-interaction`; it rebuilt both native projects and installed embedded PHP 8.4.23 with the documented default non-ICU binaries.
- Confirmed the tracked `./native` wrapper reports NativePHP 3.3.6 and the regenerated platform directory contains the Android Gradle and iOS Xcode/CocoaPods projects.
- Kept `NATIVEPHP_DEVELOPMENT_TEAM` unset because no Apple team ID was provided and this Intel Mac cannot build the iOS shell.
- Preserved the fully on-device Laravel, Inertia, Vue, and SQLite architecture with no remote client/server integration.

### Changed Files

- `.gitignore`
- `config/nativephp.php`
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

No application migration or Composer/npm package was added, removed, or upgraded. NativePHP remains at 3.3.6 on the v3 patch line.

### Verification

- The focused RED run failed on the string build code and missing root ignore rule; after implementation, `php artisan test --compact tests/Feature/NativePhpMobileTest.php` passed with 6 tests and 41 assertions.
- `php artisan native:install both --force --no-interaction`: passed; Android and iOS shells were regenerated with PHP 8.4.23 and ICU disabled.
- `zsh -lic 'php artisan native:debug --json --no-interaction'`: passed with NativePHP 3.3.6, PHP 8.4.16, embedded PHP 8.4.23, Android Studio 2026.1.2, Gradle 8.13, Java 17.0.16, CocoaPods 1.17.0, and no optional plugins.
- `./native version --no-interaction`, `native:plugin:validate`, and `native:plugin:list --all`: passed.
- Browser smoke check for `http://xiaomi-mimo.test`: passed with HTTP 200 at the final `/login` URL before any native launch.
- Scoped Pint and Larastan for the NativePHP files passed with zero errors.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev`: passed with no known dependency vulnerabilities.
- `php artisan test --compact`: passed, 148 tests and 659 assertions.
- `npm run build`: passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.
- Full Larastan remains red with 364 existing application errors; the installation files are clean.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier verification with 13 existing files; this installation phase introduced none of them.

### Known Limitations

- iOS compilation remains unavailable on this Intel Mac because NativePHP Mobile 3 requires Apple silicon and full Xcode; no Apple development team ID was supplied.
- ICU remains disabled because neither the production dependency contract nor application source requires PHP `intl`; rerun the installer with `--with-icu` if that changes.
- Native build, run, device launch, emulator launch, and watch commands were not auto-run, per the installed NativePHP project guidance.

### Git Delivery

- Commit `0366361` (`chore: reconcile NativePHP installation guide`) contains only the root ignore rule, NativePHP build-code configuration, focused installation tests, and phase progress.
- Push to `origin main` succeeded (`c04d7a3..0366361`).
- Unrelated staged sidebar, navigation, task, project, export, planning, and shared model/middleware work remains excluded and preserved.

## NativePHP Mobile 3 Configuration Guide Reconciliation

### Status

Completed.

### Completed Work

- Reconciled the effective NativePHP Mobile 3 configuration with the official configuration guide and the installed 3.3.6 package implementation.
- Kept the stable reverse-DNS app ID, debug development version, numeric build code, root start path, persistent runtime, SQLite storage, portrait-first phone orientation, and iPad support disabled.
- Documented the optional deep-link, Apple team, App Store Connect, Android status-bar, and local development-server environment variables without inventing production hosts or credentials.
- Made Android status-bar behavior environment-configurable with the documented `auto` default and named the local discovery service `Xiaomi Mimo`.
- Excluded `.agents`, `.github`, `.mimocode`, credentials, documentation, tests, transient framework state, and logs from packaged Laravel bundles while retaining required runtime and built frontend files.
- Confirmed the Android SDK invariant `compile_sdk >= target_sdk >= min_sdk`, conservative release optimization defaults, automatic system-theme status icons, and local-only hot-reload contract.
- Confirmed no optional NativePHP plugin is installed, so app-level permission descriptions and localizations remain empty until a plugin-backed device feature requires them.
- Preserved a self-contained Laravel, Inertia, Vue, and SQLite application with no remote client/server dependency.

### Changed Files

- `.env.example`
- `config/nativephp.php`
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package was added, removed, or upgraded. NativePHP remains at 3.3.6 on the v3 patch line.

### Verification

- The focused configuration test failed first on missing bundle exclusions, then `php artisan test --compact tests/Feature/NativePhpMobileTest.php` passed with 7 tests and 87 assertions.
- `php artisan config:show nativephp` confirmed the effective runtime, Android, server, hot-reload, store, iPad, and orientation values.
- `zsh -lic 'php artisan native:debug --json --no-interaction'` passed with NativePHP 3.3.6, embedded PHP 8.4.23, Android Studio 2026.1.2, Gradle 8.13, Java 17.0.16, CocoaPods 1.17.0, and no optional plugins; Xcode remains unavailable.
- `php artisan native:plugin:validate --no-interaction` and `php artisan native:plugin:list --all --no-interaction` passed with no installed plugins.
- Scoped Pint and Larastan for the NativePHP configuration files passed with zero errors.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev` passed with no known dependency vulnerabilities.
- `php artisan test --compact` passed with 149 tests and 705 assertions.
- `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.
- Full Larastan remains red with 364 existing application errors; the NativePHP configuration file is clean.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier verification with 13 existing files; none is in a configuration-phase file.

### Known Limitations

- Deep links and verified HTTPS hosts remain disabled because no product URL contract was supplied.
- iOS signing and App Store Connect upload remain unconfigured because no Apple team or API credentials were supplied; iOS compilation is also unavailable on this Intel Mac without full Xcode and Apple silicon.
- Native permission descriptions remain empty because the application has no optional NativePHP plugin or implemented device permission flow.
- Native build, run, device launch, emulator launch, and watch commands were not auto-run, per the installed NativePHP project guidance.

### Git Delivery

- Commit `825197b` (`chore: reconcile NativePHP configuration guide`) contains only the example environment, NativePHP configuration, focused test, and phase progress files.
- Push to `origin main` succeeded (`65dc904..825197b`).
- Unrelated staged and unstaged sidebar, navigation, task, project, export, profile, members, preferences, and planning work remains excluded and preserved.

## NativePHP Mobile 3 Deployment Guide Reconciliation

### Status

Completed.

### Completed Work

- Reconciled the installed NativePHP Mobile 3 release, signing, packaging, and store-upload contracts with the official deployment guide and NativePHP 3.3.6 command implementation.
- Documented the exact Android keystore, FCM, Google Play service-account, App Store Connect, iOS certificate, provisioning-profile, and team environment variables as empty placeholders without generating or committing release credentials.
- Replaced the legacy example `APP_STORE_API_KEY` variable with the current file-based `APP_STORE_API_KEY_PATH` contract while retaining the package's legacy configuration fallback.
- Added the App Store API key path to `nativephp.app_store_connect` and confirmed the valid app ID plus integer build-code contract.
- Added Android, iOS, store, and workstation-only variables to `cleanup_env_keys`, preventing signing passwords, credential paths, service-account data, team IDs, SDK paths, and FCM server keys from entering the embedded Laravel environment.
- Exercised NativePHP's real environment-cleanup trait and confirmed it removes representative deployment credentials while preserving the app ID, release version, and SQLite runtime configuration.
- Added `/credentials` to the root ignore rules; the generated `/nativephp` tree and its credentials/artifacts remain ignored separately.
- Confirmed `native:release`, `native:credentials`, `native:package`, and `native:check-build-number`, including release APK/AAB, App Store export, internal Play track, upload, and `--no-tty` contracts.
- Preserved `DEBUG` for local development; semantic release version selection, credential generation, signed packaging, and external store uploads remain explicit owner actions.
- Preserved the on-device architecture without introducing a deployment server.

### Changed Files

- `.env.example`
- `.gitignore`
- `config/nativephp.php`
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package was added, removed, or upgraded. NativePHP remains at 3.3.6.

### Verification

- The focused deployment tests failed first on the missing App Store API key path and Android secret cleanup, then `php artisan test --compact tests/Feature/NativePhpMobileTest.php` passed with 10 tests and 155 assertions.
- The cleanup regression exercised `Native\Mobile\Traits\CleansEnvFile` against representative Android, Google, App Store, and iOS credentials and confirmed no tested secret or password remained.
- `php artisan help native:release`, `native:credentials`, `native:package`, and `native:version` with JSON formatting confirmed the installed command contracts without mutating the release version, generating credentials, packaging, or uploading.
- `php artisan config:show nativephp.app_store_connect` confirmed the file-based API key path and other store values are unset until real credentials are supplied.
- `zsh -lic 'php artisan native:debug --json --no-interaction'` passed with NativePHP 3.3.6, embedded PHP 8.4.23, Android Studio 2026.1.2, Gradle 8.13, Java 17.0.16, CocoaPods 1.17.0, and no Xcode; plugin validation passed with no installed plugins.
- Scoped Pint and Larastan for the NativePHP configuration files passed with zero errors.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev` passed with no known dependency vulnerabilities.
- `php artisan test --compact` passed with 152 tests and 773 assertions.
- `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.
- Full Larastan remains red with 364 existing application errors; the NativePHP configuration file is clean.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier verification with 13 existing files; none is in a deployment-phase file.

### Known Limitations

- No Android keystore, Google service-account key, Apple certificate, provisioning profile, App Store API key, team ID, or store application access was supplied; signed packages and uploads therefore remain intentionally unavailable.
- The public semantic version is not yet selected and local development remains on `DEBUG`; `native:release` must be run deliberately when cutting the first release.
- iOS packaging remains unavailable on this Intel Mac because full Xcode and Apple silicon are absent.
- Release builds, credential generation, signed packaging, profile validation, build-number synchronization, and store uploads were not auto-run, per the installed NativePHP project guidance and because they have persistent external consequences.

### Git Delivery

- Commit `2c37d0c` (`chore: reconcile NativePHP deployment guide`) contains only the deployment environment contract, credential ignore rule, NativePHP secret cleanup, focused tests, and phase progress files.
- Push to `origin main` succeeded (`6c13c56..2c37d0c`).
- Unrelated staged and unstaged sidebar, navigation, task, project, export, profile, members, preferences, and planning work remains excluded and preserved.

## NativePHP Mobile 3 Development Guide Reconciliation

### Status

Completed.

### Completed Work

- Reconciled the installed NativePHP Mobile 3 development workflow with the official development guide and the existing Laravel 13, Inertia 3, Vue 3, and Vite 8 application.
- Confirmed `nativephpMobile()` and `nativephpHotFile()` are wired into Vite, with dedicated `build:ios` and `build:android` scripts using the required platform modes.
- Confirmed Axios is a direct production dependency and Inertia 3 is explicitly configured with `axiosAdapter()`, allowing NativePHP's build-time Axios interception to route requests through the embedded PHP runtime.
- Added `database` to the NativePHP hot-reload paths while retaining application, route, configuration, resource, and public asset watching.
- Added `/public/ios-hot` and `/public/android-hot` to the root ignore rules alongside `/public/hot` so platform-specific Vite development state cannot be committed.
- Confirmed `NATIVEPHP_APP_VERSION=DEBUG` remains active for development re-extraction and the installed `native:run`, `native:open`, and `native:watch` commands expose the documented platform and watch options.
- Avoided speculative `System::isIos()` or `System::isAndroid()` branches because the application currently has no platform-specific behavior.
- Preserved the self-contained on-device Laravel and SQLite architecture without adding a production remote client/server dependency; Vite networking remains development-only HMR.

### Changed Files

- `.gitignore`
- `config/nativephp.php`
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package was added, removed, or upgraded. Axios and all required NativePHP/Vite/Inertia packages were already installed.

### Verification

- The focused development test failed first because `database` was absent from the hot-reload paths, then `php artisan test --compact tests/Feature/NativePhpMobileTest.php` passed with 8 tests and 108 assertions.
- `php artisan config:show nativephp.hot_reload` confirmed the complete effective watch and exclusion lists.
- `php artisan help native:run --format=json`, `native:open`, and `native:watch` confirmed the installed command contracts without launching a build, IDE, device, or watcher.
- `zsh -lic 'php artisan native:debug --json --no-interaction'` passed with NativePHP 3.3.6, embedded PHP 8.4.23, Android Studio 2026.1.2, Gradle 8.13, Java 17.0.16, CocoaPods 1.17.0, and no Xcode; plugin validation passed with no installed plugins.
- Scoped Pint and Larastan for the NativePHP configuration files passed with zero errors.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev` passed with no known dependency vulnerabilities.
- `php artisan test --compact` passed with 150 tests and 726 assertions.
- `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.
- Full Larastan remains red with 364 existing application errors; the NativePHP configuration file is clean.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier verification with 13 existing files; none is in a development-phase file.

### Known Limitations

- Native iOS development remains unavailable on this Intel Mac because full Xcode and Apple silicon are absent; the configured Android toolchain is available.
- Platform-mode frontend builds, native compilation, IDE launch, device/emulator launch, and long-running watchers were not auto-run, per the installed NativePHP project guidance.
- Real-device HMR requires the device and development workstation to share a network; this is development tooling only and is not an application runtime dependency.

### Git Delivery

- Commit `30433e2` (`chore: reconcile NativePHP development guide`) contains only the hot-file ignore rules, NativePHP hot-reload configuration, focused development test, and phase progress files.
- Push to `origin main` succeeded (`e277f15..30433e2`).
- Unrelated staged and unstaged sidebar, navigation, task, project, export, profile, members, preferences, and planning work remains excluded and preserved.

## NativePHP Mobile 3 Command Reference Reconciliation

### Status

Completed.

### Completed Work

- Reconciled the official NativePHP Mobile 3 command reference with the latest installed stable release, NativePHP Mobile 3.3.6.
- Confirmed all 20 documented development, release, and plugin commands are registered through Laravel Artisan.
- Confirmed the executable root `./native` shortcut is byte-for-byte identical to the package-provided wrapper, so commands and options delegate to Artisan without a parallel implementation.
- Added focused command-definition contracts for platform and device arguments, Jump networking controls, development watch/start URL controls, signing, packaging, validation, store-upload controls, and plugin lifecycle operations.
- Locked release, export-method, and Play Store track defaults to the installed command contract and verified required release and plugin-uninstall arguments.
- Preserved the self-contained on-device Laravel and SQLite architecture without adding a remote client/server runtime.

### Changed Files

- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package was added, removed, or upgraded. `composer outdated nativephp/mobile --direct` confirmed 3.3.6 is the latest stable release.

### Verification

- The focused test failed first only because Pest does not expose an executable-file expectation for strings; the assertion was corrected to use PHP's executable-bit check. `php artisan test --compact tests/Feature/NativePhpMobileTest.php` then passed with 13 tests and 256 assertions.
- `php artisan test --compact` passed with 155 tests and 874 assertions.
- Scoped Pint passed for the modified NativePHP Pest test.
- File-scoped Larastan reports five pre-existing errors on lines before the new command-reference coverage; the newly added command-contract lines introduce no Larastan finding.
- `./native version`, `./native plugin:list --json`, and `./native plugin:validate` passed without changing application or native state; no NativePHP plugins are installed.
- Artisan JSON help output confirmed the installed `native:install`, `native:run`, `native:watch`, `native:jump`, `native:open`, `native:package`, release, credentials, build-number, and plugin signatures without invoking their operations.
- `native:debug --json` passed with NativePHP 3.3.6, embedded PHP 8.4.23, Android Studio 2026.1.2, Gradle 8.13, Java 17.0.16, CocoaPods 1.17.0, and no Xcode.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev` passed with no known dependency vulnerabilities.
- `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.
- Full Larastan remains red with 364 existing application errors.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier verification with 13 existing files; no frontend file was changed in this phase.

### Known Limitations

- The current official page advertises `native:install --fresh` and `--without-icu`, while the latest stable 3.3.6 command exposes `--force`, `--no-force`, `--with-icu`, and `--skip-php`. It also shows `native:check-build-number` without a platform argument, while 3.3.6 requires one. Tests intentionally follow the executable installed package contract rather than inventing application-level replacements for upstream command options.
- Native iOS operations remain unavailable on this Intel Mac because full Xcode and Apple silicon are absent; the Android toolchain is available.
- Native builds, IDE/device or emulator launches, Jump servers, watchers, log tailing, credential generation, release version changes, packaging, profile validation, and store uploads were not auto-run, per the installed NativePHP project guidance and because several are long-running or have persistent external consequences.

### Git Delivery

- Commit `1a87b09` (`chore: reconcile NativePHP command reference`) contains only the focused command contracts and phase progress files.
- Push to `origin main` succeeded (`022b0d7..1a87b09`).
- Unrelated staged and unstaged sidebar, navigation, task, project, export, profile, members, preferences, and planning work remains excluded and preserved.

## NativePHP Mobile 3 Architecture Overview Reconciliation

### Status

Completed.

### Completed Work

- Reconciled the official NativePHP Mobile 3 architecture overview with the installed 3.3.6 package and Laravel 13 application bootstrap.
- Confirmed the project's PHP constraint accepts the package's embedded PHP 8.4 runtime, the NativePHP package service provider is auto-discovered, and install/run commands are registered.
- Added focused contracts for the NativePHP 3.3 package range, persistent runtime mode, and native-mode selection of the package-provided `mobile_public` filesystem.
- Confirmed the development SQLite file is excluded from the application bundle while migrations remain bundled for execution against the native shell's persistent database.
- Confirmed the SQLite connection enables foreign keys, WAL journaling, normal synchronization, and a bounded busy timeout suitable for the on-device runtime.
- Confirmed the public storage link points to persistent `storage/app/public` data.
- Inspected both native shells: Android creates the persistent SQLite file then clears caches, recreates the storage link, and runs migrations; iOS performs version-aware extraction, migrations, cache clearing, and storage-link creation.
- Preserved the embedded Laravel and SQLite architecture without adding a remote web service, API client, or second application runtime.

### Changed Files

- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package was added, removed, or upgraded.

### Verification

- The first focused run exposed two invalid test probes: Composer Semver is not an application dependency, and Laravel's immutable environment repository cannot be changed after bootstrap. Both probes were replaced with dependency-free installed-version checks and a direct native-filesystem configuration contract.
- `php artisan test --compact tests/Feature/NativePhpMobileTest.php` passed with 16 tests and 277 assertions.
- `php artisan test --compact` passed with 158 tests and 895 assertions.
- Scoped Pint passed for the modified NativePHP Pest test, and scoped `git diff --check` passed.
- File-scoped Larastan reports the same five pre-existing errors on lines before the new architecture coverage; no new line reports an error.
- `php artisan config:show database.connections.sqlite` confirmed SQLite, foreign keys, 5-second busy timeout, WAL, normal synchronization, and deferred transactions.
- `php artisan config:show filesystems.links` confirmed `public/storage` targets persistent `storage/app/public`.
- `./native version` and `native:debug --json` passed with NativePHP 3.3.6, local PHP 8.4.16, embedded PHP 8.4.23, Android Studio 2026.1.2, Gradle 8.13, Java 17.0.16, CocoaPods 1.17.0, and no Xcode.
- `composer check-platform-reqs --no-dev` passed for PHP 8.4.16 and all production extensions.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, and `npm audit --omit=dev` passed with no known dependency vulnerabilities.
- `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Full Larastan remains red with 364 existing application errors.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier verification with 13 existing files; no frontend file was changed in this phase.

### Known Limitations

- Native iOS execution remains unavailable on this Intel Mac because full Xcode and Apple silicon are absent; the configured Android toolchain is available.
- The native shells' actual extraction, migration, cache, symlink, and web-view startup sequence requires a platform build and simulator or physical device for end-to-end confirmation.
- Native installation, platform-mode frontend builds, native compilation, device or emulator launch, IDE launch, and watchers were not auto-run, per the installed NativePHP project guidance.

### Git Delivery

- Commit `6d7bdbb` (`chore: reconcile NativePHP architecture overview`) contains only the focused architecture contracts and phase progress files.
- Push to `origin main` succeeded (`887d7a7..6d7bdbb`).
- Unrelated staged and unstaged sidebar, navigation, task, project, export, profile, members, preferences, and planning work remains excluded and preserved.

## NativePHP Mobile 3 Jump Reconciliation

### Status

Completed.

### Completed Work

- Reconciled the official Jump workflow with the installed NativePHP Mobile 3.3.6 proxy, managed Laravel server, native bridge, mDNS advertisement, and Vite HMR proxy.
- Added `npm run jump`, which starts `native:jump` and Vite together through the existing `concurrently` dependency and terminates both processes when either exits.
- Added focused contracts for every Jump network and fallback option: host/IP selection, HTTP, WebSocket, TCP bridge, Vite proxy, managed/BYO Laravel ports, mDNS disabling, and browser QR fallback.
- Confirmed Laravel forwards `JUMP_BRIDGE_PORT` and `JUMP_WS_PORT` into the managed `artisan serve` process.
- Confirmed the existing Vite configuration supplies both the NativePHP hot-file path and NativePHP Mobile plugin; Jump supplies the device-facing HMR proxy without another Vite server or CORS configuration.
- Kept Jump strictly development-only. The production application remains embedded Laravel plus SQLite and does not depend on a remote client/server deployment.

### Changed Files

- `package.json`
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm dependency was added, removed, or upgraded. The existing `concurrently` development dependency was reused.

### Verification

- The first focused test run failed only because the new `jump` package script did not exist, confirming the test covered the integration; it passed after the script was added.
- `php artisan test --compact tests/Feature/NativePhpMobileTest.php` passed with 18 tests and 306 assertions.
- `php artisan test --compact` passed with 160 tests and 924 assertions.
- Scoped Pint, package Prettier verification, and scoped `git diff --check` passed.
- File-scoped Larastan reports the same five pre-existing errors on lines before the new Jump coverage; no new line reports an error.
- `php artisan help native:jump --format=json` confirmed the complete installed command definition and safe defaults without starting the server.
- `./native version` and `native:debug --json` passed with NativePHP 3.3.6, local PHP 8.4.16, embedded PHP 8.4.23, Android Studio 2026.1.2, Gradle 8.13, Java 24.0.2, CocoaPods 1.17.0, and no Xcode.
- `composer validate --strict --no-check-publish`, `composer audit --no-interaction`, `composer check-platform-reqs --no-dev`, and `npm audit --omit=dev` passed with valid dependencies, satisfied production platform requirements, and no known vulnerabilities.
- `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Full Larastan remains red with 364 existing application errors.
- Full Vue type checking remains red with 9 existing errors, full ESLint with 72 existing errors, and full resource Prettier verification with 13 existing files. The only frontend manifest changed in this phase is the formatted `package.json` script entry.

### Known Limitations

- Jump still requires the NativePHP Jump companion app and a physical device on the same local network; that device handshake was not started automatically.
- The installed 3.3.6 command selects its Jump WebSocket, TCP bridge, and Vite proxy ports independently, defaults them to 3001, 3002, and 3003, and can auto-increment occupied ports. The general `NATIVEPHP_WS_PORT=8081` server setting is not the Jump runtime default; use `--ws-port` when an explicit Jump port is needed.
- Bring-your-own Laravel server mode requires the caller to expose its selected bridge port as `JUMP_BRIDGE_PORT`; the managed workflow performs this passthrough automatically.
- Jump, Vite dev mode, native builds, device or emulator launches, IDE launches, and watchers were not auto-run, per the installed NativePHP project guidance.

### Git Delivery

- Commit `6916a9f` (`chore: integrate NativePHP Jump workflow`) contains only the Jump script, focused NativePHP contracts, and phase progress files.
- Push to `origin main` succeeded (`32c0cd5..6916a9f`).
- Unrelated staged and unstaged sidebar, navigation, task, project, export, profile, members, preferences, and planning work remains excluded and preserved.

## UI Phase: Warm Precision Workspace Pages

### Status

Completed.

### Scope And Decisions

- Redesign Activity, Notifications, Calendar, and Projects as one responsive Warm Precision interface with a restrained orange accent and strong light/dark contrast.
- Preserve the existing Inertia page contracts, workspace-scoped reads, project creation flow, notification actions, and generated Wayfinder links.
- Add shared Vue presentation primitives instead of repeating page-local header and metric structures.
- Move all copy for the four pages to semantic English, Lithuanian, and Russian translations with Laravel's English fallback.
- Format dates and times with the authenticated user's locale and timezone while keeping supplied page props immutable.
- Preserve the already staged Wayfinder conversion in `resources/js/pages/projects/Index.vue` and exclude unrelated worktree changes from delivery.

### Changed Files

- Rebuilt the Activity, Notifications, Calendar, and Projects Inertia pages around a shared responsive page header and metric presentation system.
- Added typed workspace UI props, semantic English, Lithuanian, and Russian copy, and locale/timezone-aware formatting.
- Added a workspace-scoped calendar controller that supplies normalized date DTOs and fixed scoped notification read actions.
- Reworked project creation to use the Inertia v3 HTTP client and generated Wayfinder endpoints without adding a dependency.
- Added focused Pest coverage for page contracts, translations, calendar/project/activity workspace isolation, and notification mutations.

### Migrations And Packages

No migration or package change was made.

### Verification

- `php artisan test --compact tests/Feature/WorkspacePagesTest.php tests/Feature/ActivityPageTest.php tests/Feature/ProjectTest.php tests/Feature/AppNavigationTest.php` passed with 20 tests and 231 assertions.
- Scoped Larastan passed with zero errors; scoped ESLint and Prettier verification passed for every changed Vue and TypeScript file.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.
- Browser verification passed on all four pages at 1440px and 390px in light and dark modes, including filters, notification tabs/actions, calendar views, project creation, horizontal overflow, and JavaScript/page errors.
- `npm run build` passed after the final mobile-width correction; Vite transformed 3,360 modules and emitted only the existing optional `fontaine` notice.
- The full Pest suite passed with 188 tests and 1,147 assertions. The existing repository baseline remains red with 341 Larastan errors, seven Vue type errors, one ESLint error, and 24 Prettier files outside this phase.

### Known Limitations

- Full Larastan, Vue type checking, ESLint, and resource-wide Prettier verification remain blocked by unrelated application and concurrent worktree issues; all phase-scoped checks pass.
- Vite continues to report the existing optional `fontaine` font-fallback optimization notice.

### Git Delivery

- Commit `576d602` (`feat: redesign workspace overview pages`) contains only the Warm Precision pages, shared presentation/translation infrastructure, scoped backend corrections, focused tests, and this phase record.
- Push to `origin main` succeeded (`13ec033..576d602`).
- Existing localization, task, settings, import/export, attachment, NativePHP, and planning changes remain outside this phase and are preserved in the worktree.

## Frontend Localization, Wayfinder, And Task State Isolation

### Status

Completed.

### Scope And Decisions

- Replace remaining frontend user-facing English literals with semantic Laravel translation keys supplied to Inertia for English, Lithuanian, and Russian, with Laravel's English fallback.
- Route all locale-sensitive dates and numbers through one shared formatter that respects the authenticated user's language and timezone preferences.
- Remove the custom global `route()` shim and replace its consumers, hardcoded settings paths, and other application route strings with generated Wayfinder functions.
- Reset task detail, comment, and checklist draft state whenever the selected task identity changes so unsaved values cannot leak between tasks.
- Add focused Pest localization and routing contracts plus a dependency-free frontend state regression test.
- Preserve and exclude unrelated data-transfer, attachment, runtime, and design work already present in the worktree.

### Changed Files

- `app/Http/Middleware/HandleInertiaRequests.php`
- `lang/en/ui.php`, `lang/lt/ui.php`, `lang/ru/ui.php`
- Shared frontend translation, locale-formatting, task-detail state, application bootstrap, and Inertia type files under `resources/js`.
- Auth, account-security, dashboard, project, task, workspace, settings, navigation, command-palette, and notification Vue consumers under `resources/js`.
- `tests/Feature/FrontendLocalizationTest.php`, `resources/js/composables/useTaskDetailState.test.ts`
- `package.json`, `composer.json`, `tsconfig.json`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm dependency was added, removed, or upgraded. The existing package and Composer verification commands now include the dependency-free Node frontend state test.

### Verification

- The new frontend test failed first because `useTaskDetailState.ts` did not exist, then passed after implementation. It opens task A, applies unsaved detail, comment, checklist-name, and checklist-item edits, switches to task B, and confirms only task B values and empty drafts remain.
- `php artisan test --compact tests/Feature/FrontendLocalizationTest.php tests/Feature/TodoTest.php tests/Feature/AppNavigationTest.php`: passed with 19 tests and 125 assertions.
- `php artisan test --compact`: passed with 188 tests and 1,147 assertions.
- `npm run test:frontend`, `npm run types:check`, `npm run lint:check`, and `npm run build`: passed. Vite emitted only the existing optional `fontaine` notice.
- `vendor/bin/pint --dirty --format agent`, `composer validate --strict --no-check-publish`, and `git diff --check`: passed.
- Full Larastan was executed and remains red with 328 pre-existing application errors outside this frontend phase. The new translation helper return type is specified; the touched middleware still reports three existing or concurrent model/workspace-translation findings.
- Full `npm run format:check` was executed and remains red only for the pre-existing `resources/js/composables/useKeyboard.ts` and `resources/js/composables/useKeyboardShortcuts.ts`; all phase frontend files pass scoped Prettier formatting.

### Known Limitations

- Passkey relative-time strings continue to arrive preformatted from the installed passkeys package; application-owned dates and numbers now use the shared locale and timezone formatter.
- Active data-transfer, attachment, upload-runtime, and design work in the shared worktree remains excluded from this phase.

### Git Delivery

- Commit `350d19d` (`fix: localize frontend and reset task state`) contains only this phase's implementation, tests, configuration, and attributable progress entry.
- Push to `origin main` succeeded (`9791310..350d19d`).
- Unrelated staged and unstaged data-transfer, attachment, runtime, and design work remains preserved outside this phase.

## ESLint Cleanup Batch 1: Dead Frontend Bindings

### Status

Completed.

### Scope And Decisions

- Remove imports, helpers, and template loop bindings that are not consumed by the rendered pages or Pinia store.
- Keep behavior unchanged and avoid formatter-driven changes outside the flagged lines.
- Preserve and exclude the active Warm Precision workspace-page work already present in the worktree.

### Changed Files

- `resources/js/pages/projects/Show.vue`
- `resources/js/pages/settings/Backup.vue`
- `resources/js/pages/settings/Notifications.vue`
- `resources/js/pages/settings/Security.vue`
- `resources/js/pages/tasks/Index.vue`
- `resources/js/stores/todo.ts`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Verification

- `npm run lint:check` removed all 12 findings covered by this batch. The repository total moved from 73 to 62 because a concurrent Notifications-page edit added one new type-import ordering finding while this batch was running.
- `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.

### Known Limitations

- The remaining ESLint findings are intentionally deferred to later small batches.
- Concurrent Warm Precision workspace-page and task-board work remains outside this batch.

### Git Delivery

Commit message: `fix: remove dead frontend bindings`. Push to `origin main` will be attempted immediately after the isolated commit. Unrelated worktree changes remain excluded.

## Task Board Selector Cleanup

### Status

Completed.

### Scope And Decisions

- Remove the selectable board mode from the task index because it currently hides the working list without rendering a board.
- Remove the board choice from the Default View preference so the unavailable mode is not exposed through a second UI selector.
- Keep the task list, calendar preference, backend preference compatibility, dormant board component, reorder endpoints, and dependencies untouched.
- Defer a board implementation until the Vue frontend has an approved accessible drag-and-drop architecture; the installed `@dnd-kit/core` and `@dnd-kit/sortable` packages require React and cannot provide Vue keyboard sensors as currently installed.

### Changed Files

- `resources/js/pages/tasks/Index.vue`
- `resources/js/pages/settings/Preferences.vue`
- `tests/Feature/TodoTest.php`
- `tests/Feature/Settings/PreferencesTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package was added, removed, or upgraded.

### Verification

- The initial focused Pest run failed only on the two new source contracts: the task index still exposed the board toggle and Preferences still exposed `value="board"`. This confirmed both regressions before implementation.
- `php artisan test --compact tests/Feature/TodoTest.php tests/Feature/Settings/PreferencesTest.php` passed with 19 tests and 62 assertions after the UI cleanup.
- The stable full `php artisan test --compact` run passed with 162 tests and 931 assertions. A preceding parallel run had one transient dashboard failure because Vite was replacing the production manifest concurrently; rerunning against stable build output passed.
- The post-build dashboard and focused selector run passed with 21 tests and 65 assertions.
- Scoped Pint passed for the two changed Pest files. Scoped ESLint and Prettier verification passed for both changed Vue files.
- `npm run build` passed with only the existing optional `fontaine` notice.
- Full Larastan remains red with 365 existing application errors. Full Vue type checking remains red with 9 existing errors, full ESLint with 46 existing errors, and full resource Prettier verification with 17 existing files; none of the scoped selector files add an ESLint or Prettier failure.
- `git diff --check` passed.

### Known Limitations

- `BoardView.vue` and the React-only `@dnd-kit` packages remain dormant and unreachable; they were not expanded or deleted because this phase intentionally removes only the dead UI entry points.
- Backend preference compatibility still accepts the historical `board` value, but neither the task view selector nor the Default View selector offers it.

### Git Delivery

- Commit `fix: remove unavailable board selectors` contains only the two selector removals, focused Pest coverage, and this progress record.
- Push to `origin main` succeeded.
- Existing unrelated workspace UI, localization, and lint-cleanup changes remain excluded and preserved.

## ESLint Cleanup Batch 2: Task UI Control Flow

### Status

Completed.

### Scope And Decisions

- Normalize imports in four task UI components according to the repository's configured group ordering.
- Make conditional control flow explicit with braces and required statement separation.
- Remove the unused Inertia router import from the filter bar instead of suppressing the finding.
- Preserve component behavior and avoid whole-file formatting.

### Changed Files

- `resources/js/components/task/BulkActions.vue`
- `resources/js/components/task/RecurringBadge.vue`
- `resources/js/components/task/TaskCreateDialog.vue`
- `resources/js/components/task/TaskFilterBar.vue`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Verification

- `npm run lint:check` resolved all 25 findings in the four targeted task UI components. The repository total moved from 62 to 22 while concurrent calendar work removed 17 additional findings and concurrent project work introduced two type-import findings.
- `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.

### Known Limitations

- The remaining ESLint findings are intentionally deferred to later small batches.
- Concurrent localization, workspace-page, calendar, project, and task-board work remains outside this batch.

### Git Delivery

Commit message: `fix: normalize task UI control flow`. Push to `origin main` will be attempted immediately after the isolated commit. Unrelated worktree changes remain excluded.

## ESLint Cleanup Batch 3: Board And Keyboard Guards

### Status

Completed.

### Scope And Decisions

- Remove unused Vue, drag-and-drop, and store imports from the task board and keyboard helper.
- Keep the board's existing native drag event implementation intact rather than retaining unused React-oriented sortable helpers.
- Make guard clauses explicit and separate event-handling statements according to the configured ESLint rules.
- Avoid whole-file formatting or unrelated behavioral changes.

### Changed Files

- `resources/js/components/task/BoardView.vue`
- `resources/js/composables/useKeyboard.ts`
- `resources/js/composables/useKeyboardShortcuts.ts`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Verification

- The first `npm run lint:check` rerun identified two additional spacing findings inside this batch's guarded blocks; both were corrected without broad formatting.
- The final batch rerun resolved all 16 targeted board and keyboard findings. The repository total moved from 22 to one while concurrent work resolved the six other prior findings and introduced one type-import finding in a new task-detail composable.
- `npm run build` passed after both batch checks; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed.

### Known Limitations

- One ESLint finding remains in a newly added concurrent file and is deferred to a final isolated batch.
- Concurrent localization and workspace UI work remains outside this batch.

### Git Delivery

Commit message: `fix: clean board and keyboard guards`. Push to `origin main` will be attempted immediately after the isolated commit. Unrelated worktree changes remain excluded.

## Data Transfer And Upload Boundaries

### Status

Completed.

### Scope And Decisions

- Limit workspace imports to 5 MiB and 1,000 combined project/task records, with selected-format MIME, extension, structure, field, enum, date, and workspace-reference validation.
- Keep task attachments at 10 MiB while allowing only explicitly supported raster image, PDF, plain-text, CSV, and JSON MIME/extension pairs; preserve the existing 2 MiB image-only avatar contract.
- Authorize both web and API attachment uploads through the bound task before storing content.
- Execute every JSON and CSV import inside a database transaction so any later record failure rolls back the complete batch.
- Stream JSON, CSV, and Markdown exports with bounded lazy queries scoped through the authorized workspace; do not materialize complete datasets or payload strings.
- Escape spreadsheet-formula prefixes in CSV cells while preserving round-trip-compatible headers.

### Changed Files

- `app/Actions/UploadAttachment.php`
- `app/Http/Controllers/Api/AttachmentController.php`
- `app/Http/Controllers/AttachmentController.php`
- `app/Http/Controllers/ExportController.php`
- `app/Http/Controllers/ImportController.php`
- `app/Http/Requests/ImportWorkspaceRequest.php`
- `app/Http/Requests/StoreAttachmentRequest.php`
- `app/Services/ExportService.php`
- `app/Services/ImportService.php`
- `lang/en/data_transfer.php`
- `lang/lt/data_transfer.php`
- `lang/ru/data_transfer.php`
- `tests/Feature/DataTransferTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change was needed.

### Verification

- The focused test matrix first failed all 14 cases against the original implementation, demonstrating the missing size, type, row-count, rollback, authorization, streaming, scope, and formula protections.
- `php artisan test --compact tests/Feature/DataTransferTest.php`: passed, 16 tests and 62 assertions.
- The wider focused attachment/avatar/data-transfer run passed 53 tests and 513 assertions.
- Pint with `--format agent` passed for every PHP file changed by this phase.
- Scoped Larastan analysis for every changed application PHP file passed with zero errors.
- Full Larastan remains blocked by 327 pre-existing errors in unrelated actions, controllers, models, and resources.
- Full Pest passed all 195 tests with 1,178 assertions.
- `npm run test:frontend`: passed, one test.
- `npm run lint:check`: passed with zero errors or warnings.
- `npm run types:check`: remains blocked by two unrelated errors in `useAutosave.ts` and `projects/Show.vue`.
- `npm run format:check`: remains blocked by one unrelated file, `resources/js/pages/tasks/Show.vue`.
- `npm run build`: passed; Vite transformed 3,358 modules and emitted only the existing optional `fontaine` notice.

### Known Limitations

- Production PHP must allow enough multipart request overhead for the documented 10 MiB attachment and 5 MiB import limits; the local Herd runtime is configured separately.
- CSV import intentionally ignores the human-readable `Assigned To` column because names are not stable workspace-scoped identifiers.
- JSON import validates exported label, tag, and checklist structures but preserves the existing import behavior of creating projects and tasks only.

### Git Delivery

Commit message: `fix: harden data transfer boundaries`. Push to `origin main` will be attempted immediately after the isolated commit. Existing unrelated localization, workspace UI, calendar, notification, and frontend cleanup changes remain excluded and preserved.

## ESLint Cleanup Final Verification

### Status

Completed.

### Scope And Decisions

- Cleared the tracked ESLint baseline in three small code batches without disabling rules or running a broad formatter.
- Used dead-code removal, configured import ordering, explicit control-flow braces, and required statement separation to address the underlying findings.
- Preserved a top-level type-import correction in the concurrent untracked `resources/js/composables/useTaskDetailState.ts` file without staging the unrelated feature implementation.
- Kept all other active localization, workspace UI, calendar, notification, and data-transfer changes outside the lint commits.

### Changed Files

- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package was added, removed, or upgraded.

### Verification

- Final `npm run lint:check` passed with zero errors and zero warnings in the current worktree.
- Final `npm run build` passed; Vite emitted only the existing optional `fontaine` notice.
- Scoped `git diff --check` passed for every lint batch and the preserved concurrent type-import correction.
- No `eslint-disable` comment was needed.

### Known Limitations

- The corrected task-detail composable remains untracked as part of an active concurrent feature and is intentionally excluded from this delivery record's commit.
- Unrelated active worktree changes remain the responsibility of their owning phases.

### Git Delivery

- Commit `613c80d` (`fix: remove dead frontend bindings`) was pushed to `origin main`.
- Commit `bdac5ae` (`fix: normalize task UI control flow`) was pushed to `origin main`.
- Commit `4ed0952` (`fix: clean board and keyboard guards`) was pushed to `origin main`.
- This final delivery record will be committed as `docs: record ESLint cleanup delivery` and pushed separately to `origin main`.

## UI Phase: Warm Precision Across All Pages

### Status

Completed.

### Before Implementation Inventory

- Confirmed `/projects` as the live visual reference: warm neutral canvas, orange editorial accent, oversized page title, integrated metric strip, rounded collection surfaces, and restrained depth.
- Inventoried every routed Inertia page and found the Warm Precision system already present on Activity, Notifications, Calendar, and Projects, while Dashboard, Tasks, task/project detail, Workspaces, Settings, and Auth still used starter-kit styling.
- Preserved the active localization, transfer/upload, Wayfinder, and task-state work already present in the dirty worktree.

### Scope And Decisions

- Extend the shared `WorkspacePageHeader` and `WorkspaceMetric` presentation to Dashboard, Tasks, task detail, project detail, and Workspaces without changing their Inertia contracts or state-change behavior.
- Give Settings and every Fortify/Auth page shared responsive shells that carry the same orange line, rounded white surface, warm background, and light/dark treatment.
- Align common cards, buttons, inputs, select triggers, dialogs, headings, focus rings, corner radius, and sidebar tokens so secondary pages and nested forms inherit the design automatically.
- Keep all copy semantic and translated in English, Lithuanian, and Russian; add Workspaces header and metric labels to each locale.
- Correct the task index's visible pagination total to read Laravel resource pagination from `meta.total`, with backward-compatible fallbacks.

### Changed Files

- Shared theme and primitives in `resources/css/app.css`, `resources/js/components/Heading.vue`, and the card, button, input, select, and dialog UI components.
- Shared shells in `resources/js/layouts/auth/AuthSimpleLayout.vue` and `resources/js/layouts/settings/Layout.vue`.
- Warm Precision page implementations in Dashboard, Tasks index/detail, Projects detail, and Workspaces index.
- English, Lithuanian, and Russian workspace copy in the three `lang/*/ui.php` catalogs.
- Focused presentation contract coverage in `tests/Feature/FrontendDesignTest.php`.

### Migrations And Packages

No migration or Composer/npm package was added, removed, or upgraded.

### Verification

- The new presentation contract first failed all five legacy page/shell datasets, then passed with 6 tests and 21 assertions after implementation.
- Focused frontend/design/localization/workspace/dashboard/task/project Pest coverage passed with 36 tests and 238 assertions.
- The full Pest suite passed with 196 tests and 1,181 assertions.
- Frontend state tests passed, and full ESLint plus full resource Prettier verification passed with zero findings.
- Scoped Vue type checking passes for every changed file; the full check is blocked only by the existing `useAutosave.ts` import of the unavailable `@vueuse/core` `debounce` export.
- The production Vite build passed with only the existing optional `fontaine` notice.
- Pint and `git diff --check` passed. Full Larastan remains red with 327 existing application errors; this frontend-only phase adds no PHP application error.
- Browser verification passed at 1440px and 390px for Dashboard, Tasks, Project detail, Workspaces, Settings, and Auth, including the dark mobile Tasks view. All checked pages had no horizontal overflow, console errors, or JavaScript errors.

### Known Limitations

- The repository-wide Vue type check remains blocked by the unrelated `resources/js/composables/useAutosave.ts` import noted above.
- Full Larastan retains its existing application-wide backlog of 327 errors.
- The optional `fontaine` build notice remains unchanged because resolving it requires a dependency or build-configuration decision outside this UI phase.

### Git Delivery

- Commit `bf2ed41` (`feat: extend warm precision design`) was pushed successfully to `origin main`.
- This delivery record will be committed as `docs: record warm precision delivery` and pushed separately.

## Frontend Formatting Cleanup

### Status

Completed.

### Scope And Decisions

- Run `npm run format:check` before editing; it reported only the two keyboard composables listed below.
- Run the project's `npm run format` script through a temporary scoped ignore file so Prettier could write only the explicitly reported files.
- Keep the cleanup formatting-only: the final commit diff contains line wrapping and indentation changes with no logic, dependency, or generated-file changes.
- Preserve all unrelated staged and unstaged work in the shared worktree.

### Changed Files

- `resources/js/composables/useKeyboard.ts`
- `resources/js/composables/useKeyboardShortcuts.ts`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change was made.

### Verification

- `npm run format:check`: passed; all files under `resources/` match Prettier style.
- `npm run lint:check`: passed as part of `composer run ci:check`.
- `npm run test:frontend`: passed, 1 test.
- `npm run build`: passed; Vite transformed 3,358 modules and generated the production bundle. The existing optional `fontaine` notice remains non-blocking.
- `php artisan test --compact`: passed, 196 tests and 1,181 assertions.
- `git diff --check`: passed, and the isolated cleanup commit was reviewed as formatting-only.
- `npm run types:check`: remains blocked by the unrelated `useAutosave.ts` import of a non-exported `debounce` member from `@vueuse/core`.
- `composer run lint:check`: remains blocked by pre-existing Pint findings in 21 unrelated PHP files.
- `composer run types:check`: remains blocked by 327 pre-existing Larastan errors.

### Known Limitations

- The repository-wide PHP and Vue type/style baselines listed above remain outside this formatting-only cleanup.
- The optional `fontaine` build notice was not changed because it is unrelated to formatting.

### Git Delivery

- Commit `be44072` (`style: format keyboard composables`) contains only the two formatting fixes and was pushed to `origin main`.
- This delivery record is committed and pushed separately so the cleanup commit remains isolated.

## End-To-End Verification And Critical/High Finding Handoff

### Status

Completed with every requested verification command passing.

- Critical findings: 0 resolved, 2 partially resolved, and 4 open.
- High findings: 4 resolved, 1 partially resolved, and 5 open.

### Exact Verification Results

| Command | Exit | Current status |
| --- | ---: | --- |
| `vendor/bin/pint --dirty --format agent` | 0 | Passed with no reported findings. |
| `php artisan test --compact` | 0 | Passed: 332 tests and 1,442 assertions. |
| `vendor/bin/phpstan analyse --no-progress` | 0 | Passed with zero errors. |
| `npm run types:check` | 0 | Passed with zero Vue/TypeScript errors. |
| `npm run lint:check` | 0 | Passed with zero ESLint errors or warnings. |
| `npm run format:check` | 0 | Passed; all files under `resources/` match Prettier style. |
| `npm run build` | 0 | Passed; Vite transformed 3,362 modules and built the production bundle. The optional `fontaine` fallback notice remains non-blocking. |

### Original Critical Findings

| Finding from `docs/current-state.md` | Status | Current evidence and next work |
| --- | --- | --- |
| Bulk task updates/deletes accept global IDs and execute unscoped `Todo::whereIn(...)`. | Open | `BulkActionRequest`, `BulkUpdateTodos`, and `BulkDeleteTodos` still accept/query global IDs. Scope the exact submitted set through the authorized workspace and reject mixed/foreign IDs atomically with regression tests. |
| Task relationships and label/tag attachment accept globally existing foreign IDs. | Open | Todo requests still use global `exists` rules for projects, assignees, parents, labels, and tags; attach actions still sync global label/tag IDs. Add workspace-scoped validation and cross-workspace rollback tests. |
| Checklist, label, tag, reminder, and attachment writes lack complete policy authorization. | Partially resolved | Attachment upload/download/delete now uses an authorized request plus `AttachmentPolicy`; checklist, label, tag, and reminder-create mutations still lack complete policy/scoped-child authorization. Finish policies and attacker/victim API/web tests for every child type. |
| Backup endpoints lack owner policy/path safety and copy only the main WAL-mode SQLite file. | Open | `BackupController` still exposes physical paths and accepts route filenames; `BackupService` still uses direct `File::copy()` for the main database. Redesign backup/restore around owner authorization, canonical identifiers, SQLite-safe snapshots, validation, and isolated restore tests. |
| Invitations create users with the known password `password` and no acceptance token. | Open | `InviteToWorkspace` still calls `firstOrCreate()` with `bcrypt('password')`; no invitation model/token acceptance flow exists. Replace account creation with expiring single-use invitations and acceptance tests. |
| Live SQLite domain referential integrity is not enforced. | Partially resolved | The applied corrective migration now enforces foreign keys across the core workspace domain, and the live database passes `PRAGMA foreign_key_check`; todo parent links and UUID-backed passkey/notification/Sanctum morph references remain incomplete, and domain `CHECK` constraints are still absent. Finish those schema contracts with populated-upgrade and integrity tests. |

### Original High Findings

| Finding from `docs/current-state.md` | Status | Current evidence and next work |
| --- | --- | --- |
| Workspace member removal uses an undefined variable and can remove final ownership. | Resolved | The controller now uses the bound `userId`, rejects owner/self removal, verifies membership, and focused settings-member tests cover normal removal and owner protection. |
| Workspace switching stores a session ID that `User::currentWorkspace()` ignores. | Resolved | `currentWorkspace()` now accepts the selected ID, verifies it through the user's membership relation, and callers pass `current_workspace_id`; navigation/page tests cover selected-workspace behavior. |
| API tokens have unrestricted abilities and API login lacks an explicit limiter. | Open | Auth still creates tokens without abilities and the API login/register routes have no explicit throttle. Define abilities, enforce them per route/action, and add login-rate and token-scope tests. |
| Nested project/task-child route binding is not scoped to the parent workspace/task. | Open | Routes still bind many projects, todos, checklists, labels, tags, reminders, and attachments globally without scoped bindings or equivalent parent checks. Add scoped bindings plus mismatched-parent tests. |
| Web/API controllers duplicate behavior, mix Inertia/JSON, and return inconsistent envelopes. | Open | Separate web/API controllers still duplicate domain paths; `TodoController` still branches on `expectsJson()` and endpoint envelopes differ. Consolidate through shared actions/query objects and one versioned JSON contract. |
| Route closures resolve workspaces, query todos, manufacture props, and invoke controllers. | Open | The main web routes still call controllers through `app(...)` from closures and perform workspace/query/presentation work. Move them to named controller/query-object endpoints. |
| Model progress accessors and dashboard/project/recurrence paths have excessive-query risks. | Open | `Todo::progress` and `Checklist::progress` still execute queries; dashboard weekly metrics issue per-day queries and no query-budget tests exist. Replace accessor queries with loaded aggregates/query objects and add deterministic query-count coverage. |
| Import/export/upload workflows lack boundary, content, streaming, and rollback controls. | Resolved | Imports now enforce size/type/record/structure boundaries and transactions; attachments enforce authorized MIME/extension/size pairs; exports stream workspace-scoped lazy queries and neutralize CSV formulas. `DataTransferTest` covers these controls. |
| Board mode is selectable but not rendered, with mixed/inaccessible drag behavior. | Partially resolved | The unavailable board selector and unused `@dnd-kit` imports were removed, leaving the supported task list honest. The dormant `BoardView` still relies on native pointer drag and has no keyboard-accessible implementation; either remove it or complete the accessible board phase. |
| Frontend copy/locale/routes and task-detail draft state are inconsistent. | Resolved | Application copy is supplied through English/Lithuanian/Russian catalogs, date/number helpers use user locale/timezone with English fallback, generated Wayfinder routes replace the custom helper/hardcoded settings paths, and task identity changes reset detail/comment/checklist drafts with regression coverage. |

### Next Phase Starting Point

- Security first: close the two cross-workspace task ID findings, complete child policies/scoped binding, and add attacker/victim atomicity tests.
- Then address invitation and API token security before exposing those flows beyond trusted local use.
- Treat backup/restore and SQLite referential integrity as schema/storage phases requiring isolated databases and populated-safe migration tests.
- Keep the now-green Pint, Pest, Larastan, Vue type, ESLint, Prettier, and production-build gates green while security/schema phases proceed.
- Decide whether the board is being removed permanently or implemented as an accessible keyboard-capable feature before re-exposing a board preference.

### Session Git Delivery

The ESLint and formatting work attributable to this conversation is present on `origin/main` in this order:

1. `613c80d` — `fix: remove dead frontend bindings`
2. `bdac5ae` — `fix: normalize task UI control flow`
3. `4ed0952` — `fix: clean board and keyboard guards`
4. `13ec033` — `docs: record ESLint cleanup delivery`
5. `be44072` — `style: format keyboard composables`
6. `de61ce9` — `docs: record frontend formatting cleanup`
7. `b700964` — `docs: record verification and audit handoff`

This repeat verification is committed and pushed separately. Commits from interleaved workspace, localization, data-transfer, design, and static-analysis phases are current-state evidence but are not claimed as part of the ESLint/formatting commit chain.

## UI Phase: Projects-Style Settings Shell

### Status

Completed.

### Scope And Decisions

- Audit every reachable Inertia page against `/projects` at desktop, mobile, and dark-mode breakpoints.
- Promote the shared Warm Precision project header into the persistent settings layout so every settings route uses the same page hierarchy, orange rail, decorative geometry, spacing, and responsive behavior.
- Remove duplicate local settings headings while preserving page actions, workspace metrics, accessibility, translations, and existing form behavior.

### Changed Files

- `resources/css/app.css`
- `resources/js/components/shared/WorkspacePageHeader.vue`
- `resources/js/layouts/settings/Layout.vue`
- `resources/js/pages/calendar/Index.vue`
- `resources/js/pages/settings/Backup.vue`
- `resources/js/pages/settings/Export.vue`
- `resources/js/pages/settings/Members.vue`
- `resources/js/pages/settings/Notifications.vue`
- `resources/js/pages/settings/Preferences.vue`
- `resources/js/pages/settings/Profile.vue`
- `resources/js/pages/settings/Security.vue`
- `resources/js/types/navigation.ts`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Verification

- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact tests/Feature/FrontendDesignTest.php`: passed, 17 tests and 56 assertions.
- `php artisan test --compact`: passed, 207 tests and 1,216 assertions.
- `npm run lint:check`: passed with zero ESLint errors or warnings.
- `npm run format:check`: passed for all files under `resources/`.
- `npm run build`: passed; Vite transformed 3,358 modules and generated the production bundle. The existing optional `fontaine` notice remains non-blocking.
- Browser verification passed for all 13 reachable authenticated list/settings routes at a 390 px viewport, with zero horizontal overflow and no console or page errors. Desktop settings headers match the `/projects` header width and structure; dark mode and the guest login, registration, and password-reset-entry routes were also verified.
- `npm run types:check`: remains blocked only by the unrelated pre-existing `resources/js/composables/useAutosave.ts` import of a non-exported `debounce` member from `@vueuse/core`.
- `vendor/bin/phpstan analyse --no-progress`: retains the pre-existing application-wide backlog of 327 errors; this frontend-only phase added no PHP source changes.

### Known Limitations

- The existing Vue type-check and Larastan baselines listed above remain outside this visual phase.
- The security settings route requires an independent recent password-confirmation session; its shared shell is covered by the same layout implementation, source test, production build, and auth-shell browser verification.
- The optional `fontaine` build notice was not changed because it requires a dependency or build-configuration decision outside this phase.

### Git Delivery

- Commit `8c0db82` (`feat: align settings with projects design`) was pushed successfully to `origin main`.
- This final delivery record will be committed as `docs: record projects-style settings delivery` and pushed separately.

## UI Phase: Complete Projects-Style Visual Unification

### Status

Completed.

### Scope And Decisions

- Treat `/projects` as the single Warm Precision reference for every reachable page, dialog, drawer, confirmation, form control, empty state, and authentication surface.
- Introduce shared projects-style dialog and state primitives before migrating feature screens, with viewport-safe mobile scrolling and token-based light/dark styling.
- Replace browser-native confirmations and checkboxes with accessible application components while preserving existing routes, permissions, validation, and Inertia behavior.
- Align task details, settings interiors, list states, and guest authentication with the same orange rail, geometric accent, radius, hierarchy, and spacing used by `/projects`.
- Verify focused frontend contracts, the full test suite, static analysis, formatting, production build, and representative browser routes at desktop/mobile in light/dark mode.

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Changed Files

- Shared projects-style primitives: `WorkspaceDialogContent.vue`, `WorkspaceConfirmDialog.vue`, `EmptyState.vue`, and the localized Sheet close label.
- Dialogs and destructive flows: project/task/workspace creation, task deletion, account deletion, member removal, passkey removal, two-factor setup/disable, and backup restore.
- Main experiences: dashboard tokens, task/project lists, task details, workspaces, activity, notifications, and reusable task controls.
- Settings and guest surfaces: backup, export/import, members, notifications, profile, security, and `AuthSimpleLayout.vue`.
- English, Lithuanian, and Russian UI catalogs plus `FrontendDesignTest.php`.

### Verification

- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact tests/Feature/FrontendDesignTest.php`: passed, 39 tests and 107 assertions.
- `php artisan test --compact`: passed on the isolated rerun, 229 tests and 1,267 assertions. An earlier parallel run raced the production build while its Vite manifest was being replaced; the clean post-build rerun passed.
- `npm run lint:check`: passed with zero ESLint errors or warnings.
- `npm run format:check`: passed for all files under `resources/`.
- `npm run build`: passed; Vite transformed 3,362 modules and generated the production bundle. The existing optional `fontaine` notice remains non-blocking.
- `npm run types:check`: the phase adds no new errors; the repository remains blocked only by the pre-existing `resources/js/composables/useAutosave.ts` import of a non-exported `debounce` member from `@vueuse/core`.
- `vendor/bin/phpstan analyse --no-progress`: retains the pre-existing application-wide backlog of 327 errors; this visual phase did not add application PHP logic.
- Browser route matrix: 13 authenticated routes passed at 1440x1000 and 390x844 in both light and dark modes (52 combinations), all with HTTP 200, no horizontal overflow, and no captured console/page errors. The protected security route correctly rendered the projects-style password confirmation screen.
- Interaction QA: project, workspace, and task creation dialogs all render at 28 px radius with the shared left rail; the mobile dark task dialog fits at 347x790 inside a 390x844 viewport without document overflow. Task detail, destructive confirmation, settings export/import, and guest registration were visually inspected.

### Known Limitations

- The existing Vue type-check and Larastan baselines listed above remain outside this visual phase.
- Laravel Boost's buffered browser log still contains historical errors from older asset hashes and unrelated route warnings; fresh isolated navigations did not reproduce page or console errors.
- The optional `fontaine` build notice was not changed because it requires a dependency or build-configuration decision outside this phase.

### Git Delivery

- Commit `8ea6e40` (`feat: unify pages with projects design`) was pushed successfully to `origin main`.
- This phase record will be committed and pushed separately while preserving unrelated pre-existing progress changes.

## UI Phase: Frontend Baseline And Dormant Surface Closure

### Status

Completed.

### Scope And Decisions

- Remove the remaining Vue type-check failure in the autosave composable with lifecycle-safe native Vue watcher cleanup.
- Make dormant authentication and header layouts delegate to the canonical projects-style shells so future route changes cannot reintroduce the old visual language.
- Extend the shared Warm Precision state surface with accessible loading and error variants while preserving every existing empty-state call site.
- Re-run focused contracts, the full frontend verification stack, production build, and representative browser coverage before delivery.

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Changed Files

- `resources/js/composables/useAutosave.ts`
- `resources/js/layouts/auth/AuthCardLayout.vue`
- `resources/js/layouts/auth/AuthSplitLayout.vue`
- `resources/js/layouts/app/AppHeaderLayout.vue`
- `resources/js/components/shared/EmptyState.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Verification

- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact tests/Feature/FrontendDesignTest.php`: passed, 42 tests and 124 assertions.
- `php artisan test --compact`: passed, 232 tests and 1,284 assertions.
- `npm run test:frontend`: passed, 1 test.
- `npm run types:check`: passed with zero Vue or TypeScript errors; the previous autosave baseline is closed.
- `npm run lint:check`: passed with zero ESLint errors or warnings.
- `npm run format:check`: passed for all files under `resources/`.
- `npm run build`: passed; Vite transformed 3,362 modules and generated the production bundle. The existing optional `fontaine` notice remains non-blocking.
- `vendor/bin/phpstan analyse --no-progress`: retains the pre-existing application-wide backlog of 327 errors; this phase changed no application PHP logic.
- Browser verification passed for `/projects`, `/tasks`, `/workspaces`, `/settings/preferences`, and `/settings/backup` at 1440x1000 light mode and 390x844 dark mode: 10/10 HTTP 200 responses, zero horizontal overflow, and no fresh console or page errors. The mobile dark `/projects` reference was also visually inspected after the production build.

### Known Limitations

- The application-wide Larastan backlog is not part of this frontend closure phase.
- The optional `fontaine` build notice remains unchanged because resolving it requires a dependency or build-configuration decision outside this phase.

### Git Delivery

- Commit `3b09177` (`fix: close frontend visual baseline`) was pushed successfully to `origin main`.
- This final phase record will be committed separately while preserving unrelated pre-existing progress changes.

## Static Analysis Phase: Typed Eloquent Foundation

### Status

Completed.

### Scope And Decisions

- Treat the 327-error Larastan report as a set of shared typing defects rather than suppressing individual findings.
- Start with model relationship generics, factory generics, typed local scopes, cast-aware properties, and the incorrect morph relation return type because these defects propagate into actions, policies, resources, factories, and seeders.
- Preserve runtime queries and domain behavior; add PHPDoc only where native PHP cannot express Laravel's generic relationship and factory types.
- Re-run focused model tests and the complete Larastan report to measure the reduction before selecting the next batch.

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Changed Files

- Typed models: `ActivityLog`, `Checklist`, `ChecklistItem`, `Comment`, `Label`, `Project`, `Reminder`, `Tag`, `Todo`, `User`, `UserPreference`, `Workspace`, and `WorkspaceMember`.
- `tests/Feature/ModelRelationsTest.php`
- `docs/progress.md`

### Verification

- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact tests/Feature/ModelRelationsTest.php`: passed, 47 tests and 47 assertions across every declared relation plus membership-role behavior.
- `php artisan test --compact`: passed, 279 tests and 1,331 assertions.
- `vendor/bin/phpstan analyse --no-progress -v`: reduced the baseline from 327 errors across 104 files to 196 errors across 75 files. All findings under `app/Models` are resolved.
- `git diff --check`: passed.
- No frontend files changed, so Vue type checking, ESLint, Prettier, and the Vite build were not repeated in this backend-only batch.

### Known Limitations

- 196 Larastan findings remain in downstream resources, actions, services, controllers, policies, factories, seeders, migrations, configuration, and routes for later batches.

### Git Delivery

- Commit `a1a076c` (`fix: type eloquent model contracts`) was pushed successfully to `origin main`.
- This final phase record will be committed separately while preserving unrelated pre-existing progress changes.

## Static Analysis Phase: Typed API Resources

### Status

Completed.

### Scope And Decisions

- Bind every JSON resource to its concrete Eloquent model with `@mixin` and declare generic `array<string, mixed>` payloads without changing response keys or relation-loading behavior.
- Preserve the nullable legacy `avatar` payload through explicit attribute access instead of inventing a new API contract.
- Correct the Todo date and Reminder enum cast annotations exposed by resource analysis.
- Measure the downstream effect through the complete Larastan report rather than adding suppressions or a baseline file.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `app/Http/Resources/ActivityLogResource.php`
- `app/Http/Resources/ChecklistItemResource.php`
- `app/Http/Resources/ChecklistResource.php`
- `app/Http/Resources/CommentResource.php`
- `app/Http/Resources/LabelResource.php`
- `app/Http/Resources/ProjectResource.php`
- `app/Http/Resources/ReminderResource.php`
- `app/Http/Resources/TagResource.php`
- `app/Http/Resources/TodoResource.php`
- `app/Http/Resources/UserResource.php`
- `app/Http/Resources/WorkspaceResource.php`
- `app/Models/Reminder.php`
- `app/Models/Todo.php`
- `tests/Feature/ApiResourceTypingTest.php`
- `docs/progress.md`

### Verification

- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact tests/Feature/ApiResourceTypingTest.php tests/Feature/Api`: passed, 45 tests and 119 assertions.
- `php artisan test --compact`: passed, 292 tests and 1,369 assertions.
- `vendor/bin/phpstan analyse --no-progress -v`: reduced the baseline from 196 errors across 75 files to 97 errors across 64 files. All findings under `app/Http/Resources` are resolved.
- `git diff --check`: passed.
- No frontend files changed, so Vue type checking, ESLint, Prettier, and the Vite build were not repeated in this backend-only batch.

### Known Limitations

- 97 Larastan findings remain outside the model and API resource layers.

### Git Delivery

- Commit `3b00e94` (`fix: type API resource contracts`) was pushed successfully to `origin main`.
- This final phase record will be committed separately while preserving unrelated pre-existing progress changes.

## Static Analysis Phase: Typed Application Layer

### Status

Completed.

### Scope And Decisions

- Add concrete validated-data shapes to actions and services instead of weakening their array parameters to uninformative iterables.
- Type Todo query pipelines with `Builder<Todo>`, normalize sort direction to Laravel's supported literal values, and preserve filter behavior.
- Correct real defects exposed by Larastan: the missing `ReminderPolicy` import, the backup file timestamp call, enum assignments, the recurring-task Carbon type, the backup download response type, and the non-exhaustive bulk match.
- Add standard generic return contracts to Form Requests, translations, and notifications while leaving validation rules and payload keys unchanged.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- Typed application contracts across actions, Form Requests, controllers, middleware, notifications, providers, and services.
- `app/Console/Commands/ProcessRecurringTasks.php`
- `app/Concerns/ProfileValidationRules.php`
- `tests/Feature/ApplicationLayerContractTest.php`
- `docs/progress.md`

### Verification

- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact tests/Feature/ApplicationLayerContractTest.php tests/Feature/Api/ApiProjectTest.php tests/Feature/Api/ApiTodoTest.php tests/Feature/ProjectTest.php tests/Feature/TodoTest.php tests/Feature/WorkspacePagesTest.php tests/Feature/WorkspaceTest.php`: passed, 44 tests and 228 assertions.
- `php artisan test --compact`: passed, 296 tests and 1,381 assertions.
- `vendor/bin/phpstan analyse --no-progress --error-format=json`: reduced the baseline from 97 errors across 64 files to 43 errors across 22 files. All findings under `app/` are resolved.
- `git diff --check`: passed.
- No frontend files changed, so Vue type checking, ESLint, Prettier, and the Vite build were not repeated in this backend-only batch.

### Known Limitations

- Factory, seeder, migration, configuration, and route findings remain for the final static-analysis batch.

### Git Delivery

- Commit `eab814b` (`fix: type application layer contracts`) was pushed successfully to `origin main`.
- This final phase record will be committed separately while preserving unrelated pre-existing progress changes.

## Static Analysis Phase: Supporting Infrastructure

### Status

Completed.

### Scope And Decisions

- Complete the Larastan cleanup in configuration, factories, migrations, seeders, and routes without adding suppressions or a baseline.
- Bind every Eloquent factory to its concrete model and type iterable factory state explicitly.
- Separate UUID column creation from a dedicated foreign-key migration so clean installations and the existing SQLite database converge on the same 24 enforced relationships.
- Finish with the complete backend and frontend verification matrix because this is the final repository-wide static-analysis phase.

### Migrations And Packages

- Added `2026_07_19_185503_add_foreign_keys_to_workspace_tables.php` to apply and reverse the 24 constraints that the previous `uuid()->constrained()` chains did not create.
- No Composer or npm package change was made.

### Changed Files

- `app/Models/ActivityLog.php`, `Attachment.php`, `UserPreference.php`, and `WorkspaceMember.php`
- Typed factories under `database/factories/`
- `config/sanctum.php`
- Workspace-domain migrations `2026_07_18_000001` through `2026_07_18_000015`
- `database/migrations/2026_07_19_185503_add_foreign_keys_to_workspace_tables.php`
- `database/seeders/TodoSeeder.php`
- `tests/Feature/SupportingInfrastructureContractTest.php`
- `docs/progress.md`

### Verification

- Pre-migration live orphan audit: passed with zero orphaned identifiers across all 24 intended relationships.
- Temporary SQLite `migrate` -> `migrate:rollback --step=1` -> `migrate`: passed, including the new reversible foreign-key migration.
- Created local backup `storage/app/backups/backup_2026-07-19_185715.sqlite`, then applied the migration successfully to the live SQLite database.
- Live schema verification: 24 foreign keys present and `pragma_foreign_key_check` returned no violations.
- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact tests/Feature/SupportingInfrastructureContractTest.php`: passed, 32 tests and 42 assertions.
- `php artisan test --compact`: passed, 328 tests and 1,423 assertions.
- `vendor/bin/phpstan analyse --no-progress --error-format=json`: passed with zero errors. The complete cleanup reduced the original baseline from 327 errors across 104 files to zero without suppressions or a baseline file.
- `npm run types:check`: passed.
- `npm run lint:check`: passed.
- `npm run format:check`: passed.
- `npm run build`: passed after transforming 3,362 modules.
- `git diff --check`: passed.

### Known Limitations

- Vite still reports the existing optional `fontaine` optimization notice; the production build succeeds and no dependency was added without approval.

### Git Delivery

- Commit `d2c6af7` (`fix: complete static analysis contracts`) was pushed successfully to `origin main`.
- This final phase record will be committed separately while preserving unrelated pre-existing progress changes.

## UI Follow-up Phase: Interaction Surface Consistency

### Status

Completed.

### Scope And Decisions

- Re-audit every reachable Inertia page at desktop and mobile widths against the `/projects` visual contract.
- Normalize the remaining Activity and appearance segmented controls to the shared muted/card treatment.
- Preserve whole-row task selection while adding a real keyboard-focusable control around nested checkbox and delete actions.
- Keep all existing routes, data contracts, behavior, translations, and dark/light theme support unchanged.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `resources/js/pages/activity/Index.vue`
  - aligned the filter rail with the shared muted/card segmented-control treatment.
- `resources/js/components/AppearanceTabs.vue`
  - replaced legacy neutral/white states with shared semantic surface tokens and orange focus states.
- `resources/js/pages/tasks/Index.vue`
- `resources/js/pages/projects/Show.vue`
  - preserved whole-row task selection through a keyboard-focusable overlay button while keeping nested checkbox and delete controls independently interactive.
- `tests/Feature/FrontendDesignTest.php`
  - added structural coverage for task-row keyboard interaction and shared segmented-control styling.
- `docs/progress.md`
  - recorded the audit, implementation, verification, limitations, and delivery state.

### Verification

- Browser route audit covered 14 authenticated destinations at desktop and mobile widths; every audited page had zero horizontal overflow and no new console or page errors.
- `/settings/security` correctly redirected to the password-confirmation screen, which retained the shared authentication shell.
- Keyboard QA confirmed task-row focus, Enter navigation to task detail, and independent delete confirmation behavior.
- Dark-mode screenshots of Activity and Preferences confirmed the same muted/card/orange visual language as `/projects`.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact tests/Feature/FrontendDesignTest.php` — 45 tests passed, 141 assertions.
- `npm run types:check` — passed.
- `npm run lint:check` — passed.
- `npm run format:check` — passed.
- `php artisan test --compact` — 331 tests passed, 1440 assertions.
- `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` — passed with 0 errors.
- `npm run build` — passed.
- `git diff --check` — passed.

### Known Limitations

No functional limitation remains in the audited interaction surfaces. Laravel's persisted browser log still contains historical entries from older assets; the current desktop/mobile route audit produced no new browser errors.

### Git Delivery

- Implementation commit: `abf8312` (`fix: align interactive workspace surfaces`).
- Implementation push: successful to `origin/main`.
- Documentation commit and push: pending this record update.

## Android 12 Native Runtime Compatibility

### Status

Completed.

### Scope And Decisions

- Reproduce the Android 12 support mismatch through the NativePHP build configuration before changing application behavior.
- Keep compile and target SDK 36 while lowering only the minimum supported Android SDK to API 31.
- Preserve the existing settings UI and routing because the audited settings code contains no Android-specific native call.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `config/nativephp.php`
- `.env.example`
- `tests/Feature/NativePhpMobileTest.php`
- `docs/progress.md`

The ignored local `.env` minimum SDK override was also aligned to API 31 so local Android builds use the repaired contract.

### Verification

- TDD red phase: the Android 12 compatibility test failed as expected because the configured minimum SDK was 33 instead of 31.
- `php artisan test --compact tests/Feature/NativePhpMobileTest.php`: passed, 19 tests and 308 assertions.
- `npm run build:android`: passed after transforming 3,362 modules.
- NativePHP 3.3.6 debug APK assembly: passed; Gradle completed all 40 tasks successfully.
- Generated APK manifest inspection: confirmed `sdkVersion: 31`, `targetSdkVersion: 36`, and application ID `com.goleaf.xiaomimimo`.
- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan test --compact`: passed, 332 tests and 1,442 assertions.
- `vendor/bin/phpstan analyse --no-progress`: passed with zero errors.
- `npm run test:frontend`: passed, 1 test.
- `npm run types:check`: passed.
- `npm run lint:check`: passed.
- `npm run format:check`: passed.
- `npm run build`: passed after transforming 3,362 modules.
- `git diff --check`: passed.

### Known Limitations

An Android 12 device or API 31 emulator is not currently available in the configured local Android environment. The debug APK was compiled and its manifest was verified directly; installation was intentionally skipped by using a non-device ADB serial.

### Git Delivery

This phase is committed as `fix: support Android 12` and pushed to `origin/main` with only Android compatibility files staged; unrelated progress notes remain outside the commit.

## UI Follow-up Phase: Shared Interaction Polish

### Status

Completed.

### Scope And Decisions

- Audit shared controls that appear across authentication, sidebar, settings, and navigation flows after the page-level `/projects` design pass.
- Replace remaining neutral interaction accents with the established orange focus and action language.
- Restore keyboard access and translated accessible names for shared controls without changing their application behavior.
- Keep existing routes, Inertia data contracts, dark/light theme behavior, and component architecture unchanged.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `resources/js/app.ts`
  - changed the Inertia progress indicator from legacy gray to the `/projects` orange accent.
- `resources/js/components/PasswordInput.vue`
  - made the visibility toggle keyboard reachable, localized, state-aware, and visually aligned with the shared input focus treatment.
- `resources/js/components/TextLink.vue`
- `resources/js/components/UserInfo.vue`
  - replaced the remaining neutral link and avatar-fallback accents with the warm orange semantic treatment.
- `resources/js/components/ui/breadcrumb/Breadcrumb.vue`
- `resources/js/components/ui/input-otp/InputOTPSlot.vue`
- `resources/js/components/ui/sidebar/SidebarRail.vue`
- `resources/js/components/ui/spinner/Spinner.vue`
  - aligned shared feedback/focus surfaces and localized their accessible names.
- `resources/js/pages/auth/Login.vue`
- `resources/js/pages/auth/Register.vue`
- `resources/js/pages/auth/TwoFactorChallenge.vue`
  - removed manual positive tab ordering and normalized inline authentication actions.
- `lang/en/ui.php`
- `lang/lt/ui.php`
- `lang/ru/ui.php`
  - added stable interaction and accessibility copy in every supported language.
- `tests/Feature/FrontendDesignTest.php`
  - added shared interaction, focus, progress-accent, and translation coverage.
- `docs/progress.md`
  - recorded this phase and its verification state.

### Verification

- Dark-mode Login screenshots at 390×844 and 1440×1000 confirmed the same orange/black/card language as `/projects`, with zero horizontal overflow.
- Browser keyboard QA confirmed `Password -> Show password -> Confirm password -> Show password` follows natural DOM order across Login and Register.
- The password toggle exposes translated `aria-label` and `aria-pressed`; keyboard focus produced the orange three-pixel ring and Space changed the field type to text.
- A delayed Inertia visit exposed the live progress bar as `rgb(234, 88, 12)`.
- Desktop sidebar QA confirmed the translated rail label/title and orange hover rail; current browser sessions produced no console or page errors.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact tests/Feature/FrontendDesignTest.php` — 50 tests passed, 195 assertions.
- `php artisan test --compact` — 340 tests passed, 1519 assertions.
- `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` — passed with 0 errors.
- `npm run test:frontend` — passed, 1 test.
- `npm run types:check` — passed.
- `npm run lint:check` — passed.
- `npm run format:check` — passed.
- `npm run build` — passed.
- `git diff --check` — passed.

### Known Limitations

The Fortify two-factor challenge redirects ordinary guest sessions to Login unless a real pending two-factor authentication session exists. OTP styling and reduced-motion behavior are covered by the focused source contract, TypeScript, lint, and production build, but the protected challenge state was not artificially forged for browser QA.

### Git Delivery

- Implementation commit: `2de801c` (`fix: polish shared interaction surfaces`).
- Implementation push: successful to `origin/main`.
- Documentation commit and push: pending this record update.

## Task Detail Label Editing

### Status

Completed.

### Scope And Decisions

- Reproduced the reported task-detail issue: assigned labels were visible, but the edit form offered no way to change them.
- Reused the existing task update route and action instead of adding a parallel label-assignment endpoint.
- Load selectable labels only from the task's workspace and validate every submitted label against that same workspace before synchronizing the pivot table.
- Keep the label editor accessible with a fieldset, named checkboxes, processing states, validation feedback, and localized English, Lithuanian, and Russian copy.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `app/Http/Controllers/TodoController.php`
- `app/Http/Requests/UpdateTodoRequest.php`
- `resources/js/pages/tasks/Show.vue`
- `lang/en/tasks.php`
- `lang/lt/tasks.php`
- `lang/ru/tasks.php`
- `tests/Feature/TodoTest.php`
- `docs/progress.md`

### Verification

- TDD red phase confirmed the detail page did not expose workspace labels and the update request accepted a foreign-workspace label.
- `php artisan test --compact tests/Feature/TodoTest.php` — passed, 16 tests and 64 assertions.
- Browser QA on `/tasks/019f7a48-e93e-700c-a984-26341f7ddf06` confirmed all six workspace labels are selectable, Bug and Security are initially checked, and saving Bug plus Feature returned HTTP 303 and updated the detail metadata. The original Bug plus Security assignment was then restored and verified.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact` — passed, 340 tests and 1,519 assertions.
- `vendor/bin/phpstan analyse --no-progress` — passed with 0 errors.
- `npm run test:frontend` — passed, 1 test.
- `npm run types:check` — passed.
- `npm run lint:check` — passed.
- `npm run format:check` — passed.
- `npm run build` — passed after transforming 3,362 modules.
- `git diff --check` — passed.

### Known Limitations

- This task-detail feature assigns and removes existing workspace labels; label creation and renaming remain in the workspace label-management flow.
- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.

### Git Delivery

- Implementation commit: `4012e46` (`feat: edit task labels`).
- Implementation push: successful to `origin/main`.
- This delivery record is committed and pushed separately so the feature commit remains focused.

## Transient Surface Design Unification

### Status

Completed.

### Scope And Decisions

- Extend the `/projects` Warm Precision visual language to shared dropdown, select, tooltip, dialog, sheet, toast, and mobile-sidebar surfaces.
- Standardize semantic borders, generous radii, warm shadows, orange keyboard focus, touch-friendly menu rows, and reduced-motion behavior at the shared primitive level so every consuming page inherits the same treatment.
- Replace remaining hardcoded accessibility copy in shared transient surfaces with stable English, Lithuanian, and Russian translations.
- Preserve the existing page architecture and reuse the installed Reka UI and Vue Sonner primitives without adding packages.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `resources/js/components/ui/dropdown-menu/DropdownMenuContent.vue`
- `resources/js/components/ui/dropdown-menu/DropdownMenuItem.vue`
- `resources/js/components/ui/select/SelectContent.vue`
- `resources/js/components/ui/select/SelectItem.vue`
- `resources/js/components/ui/tooltip/TooltipContent.vue`
- `resources/js/components/ui/dialog/DialogContent.vue`
- `resources/js/components/ui/dialog/DialogOverlay.vue`
- `resources/js/components/ui/dialog/DialogScrollContent.vue`
- `resources/js/components/ui/sheet/SheetContent.vue`
- `resources/js/components/ui/sheet/SheetOverlay.vue`
- `resources/js/components/ui/sonner/Sonner.vue`
- `resources/js/components/ui/sidebar/Sidebar.vue`
- `resources/js/components/ui/sidebar/SidebarTrigger.vue`
- `resources/js/components/ui/breadcrumb/BreadcrumbEllipsis.vue`
- `lang/en/ui.php`
- `lang/lt/ui.php`
- `lang/ru/ui.php`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Verification

- Light desktop browser QA on `/projects` confirmed the workspace dropdown uses the Warm Precision popover, orange keyboard highlight, pointer rows, and zero horizontal overflow.
- Light and dark browser QA on `/settings/preferences` confirmed the shared select uses the same rounded semantic surface and orange keyboard highlight; Vue Sonner resolves its theme to the active dark appearance.
- Collapsed-sidebar QA confirmed the shared tooltip uses the refined rounded inverse surface and warm border/shadow treatment.
- Mobile QA at 390×844 confirmed the translated sidebar sheet is 288px wide, uses the softened 65% overlay with 2px backdrop blur, and produces zero horizontal overflow.
- Reduced-motion browser emulation confirmed the dropdown reports `animation-name: none` and a zero-second animation duration.
- A clean browser reload and dropdown interaction on `/projects` produced no console errors or page errors.
- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact tests/Feature/FrontendDesignTest.php` — 53 tests passed, 259 assertions.
- `php artisan test --compact` — 343 tests passed, 1,583 assertions.
- `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` — passed with 0 errors.
- `npm run test:frontend` — passed, 1 test.
- `npm run types:check` — passed.
- `npm run lint:check` — passed.
- `npm run format:check` — passed.
- `npm run build` — passed after transforming 3,362 modules.
- `git diff --check` — passed.

### Known Limitations

Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.

### Git Delivery

- Implementation commit: `1cf3dad` (`fix: unify transient interface surfaces`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `9fb1573` (`docs: record transient surface unification`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Shared Control And Motion Polish

### Status

Completed.

### Scope And Decisions

- Complete the `/projects` Warm Precision contract for shared checkbox, alert, skeleton, spinner, link-focus, and compact status surfaces.
- Keep semantic invalid and disabled states while making checked, focus-visible, loading, and reduced-motion behavior consistent across authentication, tasks, workspace switching, and settings.
- Reuse the current shared primitives and semantic translations; do not introduce packages, a parallel component system, or page-specific theme variants.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `resources/js/components/ui/checkbox/Checkbox.vue`
- `resources/js/components/ui/alert/AlertTitle.vue`
- `resources/js/components/ui/alert/index.ts`
- `resources/js/components/ui/badge/index.ts`
- `resources/js/components/ui/button/index.ts`
- `resources/js/components/ui/skeleton/Skeleton.vue`
- `resources/js/components/ui/spinner/Spinner.vue`
- `resources/js/components/TextLink.vue`
- `resources/js/components/PasskeyItem.vue`
- `resources/js/components/TwoFactorRecoveryCodes.vue`
- `resources/js/components/TwoFactorSetupModal.vue`
- `resources/js/components/AppearanceTabs.vue`
- `resources/js/components/shared/EmptyState.vue`
- `resources/js/components/workspace/WorkspaceSwitcher.vue`
- `resources/js/layouts/settings/Layout.vue`
- `resources/js/pages/activity/Index.vue`
- `resources/js/pages/auth/TwoFactorChallenge.vue`
- `resources/js/pages/calendar/Index.vue`
- `resources/js/pages/notifications/Index.vue`
- `resources/js/pages/projects/Index.vue`
- `resources/js/pages/settings/Members.vue`
- `resources/js/pages/tasks/Show.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Verification

- Focused design-contract coverage passed with 70 tests and 291 assertions.
- Full Pest suite passed with 360 tests and 1,615 assertions.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,362 modules.
- Light and dark browser QA on `/settings/notifications` confirmed the shared checked state, semantic card surfaces, and zero horizontal overflow.
- Mobile browser QA at 390×844 confirmed the settings controls retain their hierarchy and produce zero horizontal overflow.
- Reduced-motion browser emulation confirmed transition properties and spinner animation resolve to `none`.
- The browser run produced no console errors or page errors.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- New pages should continue to compose these shared primitives so checked, focus, feedback, loading, and reduced-motion behavior stays aligned with `/projects`.

### Git Delivery

- Implementation commit: `1741221` (`fix: polish shared control states`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `43b26ca` (`docs: record shared control polish`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Unified Form Feedback And Progress

### Status

Completed.

### Scope And Decisions

- Extend the `/projects` Warm Precision language to validation errors, success and warning notices, and file-upload progress across authentication, task editing, and settings.
- Reuse the shared alert and field-error components so semantic colors, radii, spacing, dark mode, and assistive-technology announcements remain consistent.
- Replace duplicate page-level error markup and the browser-dependent native upload progress appearance without changing form behavior or adding packages.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `resources/js/components/InputError.vue`
- `resources/js/components/DeleteUser.vue`
- `resources/js/components/ui/alert/Alert.vue`
- `resources/js/components/ui/alert/index.ts`
- `resources/js/pages/auth/Login.vue`
- `resources/js/pages/auth/ForgotPassword.vue`
- `resources/js/pages/auth/VerifyEmail.vue`
- `resources/js/pages/settings/Profile.vue`
- `resources/js/pages/settings/Security.vue`
- `resources/js/pages/settings/Members.vue`
- `resources/js/pages/tasks/Show.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Verification

- Focused frontend-design and security coverage passed with 84 tests and 373 assertions.
- Full Pest suite passed with 369 tests and 1,656 assertions.
- A red-first regression test reproduced the Security page's stale `user.two_factor_enabled` binding; the page now consumes the controller's `canManageTwoFactor` and `twoFactorEnabled` props.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,369 modules.
- Light and dark browser QA on `/settings/security` confirmed the semantic 2FA status, accessible field-error treatment, 24px cards, 12px feedback surfaces, and zero horizontal overflow.
- Mobile browser QA at 390×844 on `/settings/profile` confirmed the profile, destructive warning, and settings navigation remain visually coherent with zero horizontal overflow.
- A failed password update safely exercised the validation state and confirmed the invalid input, error icon, error copy, and `aria-invalid` state without changing account data.
- The final browser run produced no console errors or page errors.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- Future forms should use `InputError` and semantic `Alert` variants instead of page-level red, green, or amber feedback markup.

### Git Delivery

- Implementation commit: `8e3bd05` (`fix: unify form feedback surfaces`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `7a50f21` (`docs: record form feedback unification`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Create Dialog Form Unification

### Status

- Completed.

### Scope And Decisions

- Align the active project and task creation dialogs with the Warm Precision form language established on `/projects`.
- Reuse the shared button, input, select, error, and loading components instead of repeating local control styles.
- Complete disabled, invalid, loading, keyboard-focus, dark-mode, and reduced-motion states for the affected controls.
- Preserve the existing submission behavior, validation contract, routes, and translations.
- Keep dormant dropdown, sidebar, command-palette, and filter variants unchanged because they are not rendered by current application routes.

### Changed Files

- `resources/js/components/project/ProjectCreateDialog.vue`
- `resources/js/components/task/TaskCreateDialog.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change was made.

### Verification

- Focused frontend-design coverage passed with 83 tests and 354 assertions.
- Full Pest coverage passed with 373 tests and 1,678 assertions.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,369 modules.
- Browser QA confirmed the project dialog in desktop light mode and the task dialog in desktop dark mode use the shared 24px/28px Warm Precision surface, 44px actions, semantic selected states, and zero horizontal overflow.
- Mobile browser QA at 390×844 confirmed both dialogs resolve to 358px with 16px viewport gutters, internal vertical scrolling, complete footer actions, and zero page or dialog overflow.
- An empty task-title submission safely confirmed the field error, `aria-invalid="true"`, and semantic alert without persisting data.
- The final browser interactions produced no console errors or page errors; the Boost browser log contained only older entries from earlier resolved or unrelated sessions.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- Dormant variants should be reviewed when a route begins rendering them, rather than adding unexercised styling now.

### Git Delivery

- Implementation commit: `605977d` (`fix: unify creation dialog controls`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `e3a8462` (`docs: record creation dialog unification`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Authentication Action And Validation Unification

### Status

- Completed.

### Scope And Decisions

- Align every active Fortify and passkey action with the 44px shared button rhythm used by the `/projects` creation flows.
- Add consistent processing guards and shared loading indicators to authentication submissions, including both two-factor challenge modes.
- Expose validation state through `aria-invalid` on credential, reset, confirmation, OTP, and recovery-code controls.
- Preserve Fortify routes, request payloads, translations, password rules, passkey behavior, and authentication redirects.
- Use Inertia's `disable-while-processing` contract to prevent duplicate interaction for every Fortify form while retaining the existing explicit disabled button states.
- Keep the two-factor challenge behavior unchanged and verify its conditional screen through source contracts because Fortify only renders it during a pending two-factor login.

### Changed Files

- `resources/js/components/PasskeyVerify.vue`
- `resources/js/pages/auth/*.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change was made.

### Verification

- The focused RED run produced 14 expected failures for missing large actions, processing guards, invalid states, and the passkey action contract.
- Focused frontend-design coverage passed after implementation with 97 tests and 384 assertions.
- Full Pest coverage passed with 387 tests and 1,708 assertions.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,369 modules.
- Guest route QA covered login, registration, forgot-password, reset-password, and the guarded two-factor route in desktop light and mobile dark modes with HTTP 200 results, zero horizontal overflow, and no console or page errors.
- Live measurement confirmed 44px email/password inputs, passkey actions, primary authentication actions, and password-confirmation actions.
- A single invalid demo login safely confirmed the semantic alert and `aria-invalid="true"` state without changing account data; the demo session was then restored successfully.
- The Boost browser log contained only historical entries from the previously repaired Security screen; the current auth run produced no new log entries.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- Email verification and the two-factor challenge depend on account/session states not created during visual QA; both remain covered by their unchanged Fortify feature tests and the new frontend source contracts.

### Git Delivery

- Implementation commit: `8078d03` (`fix: align authentication action states`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `cf90e7b` (`docs: record authentication action unification`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Remaining Active Form Action Unification

### Status

- Completed.

### Scope And Decisions

- Align the workspace creation, preference, notification, member invitation, and member removal actions with the 44px shared control rhythm used by `/projects`.
- Reuse the shared spinner, button sizes, field validation states, and processing guards instead of repeating page-local orange or loading styles.
- Disable mutable form controls during submission to prevent conflicting edits and duplicate requests.
- Preserve existing routes, request payloads, translations, permissions, and success behavior.
- Correct the mobile notification option hierarchy found during visual QA so each title and description remains readable instead of collapsing into adjacent text columns.

### Changed Files

- `resources/js/pages/workspaces/Index.vue`
- `resources/js/pages/settings/Preferences.vue`
- `resources/js/pages/settings/Notifications.vue`
- `resources/js/pages/settings/Members.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change was made.

### Verification

- The initial focused RED run produced four expected failures for the missing shared loading, disabled, invalid, and 44px action contracts; a separate mobile hierarchy contract also failed before the notification copy repair.
- Focused frontend-design coverage passed after implementation with 101 tests and 410 assertions.
- Full Pest coverage passed with 391 tests and 1,734 assertions.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,369 modules.
- Desktop light browser QA confirmed the workspace dialog and Preferences save action use 44px shared inputs and actions with zero horizontal overflow.
- Mobile dark browser QA confirmed Notifications and Members use 44px save, invite, input, and select controls with zero horizontal overflow.
- Visual inspection caught and verified the repaired vertical title/description hierarchy inside all mobile notification option cards.
- The final browser interactions produced no console errors or page errors; the Boost browser log contained only historical entries dated 2026-07-19.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- Dormant form variants should be reviewed when a route begins rendering them, rather than adding unexercised styling now.

### Git Delivery

- Implementation commit: `88248f0` (`fix: align remaining active form actions`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `f6989cd` (`docs: record active form action unification`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Final Async Action State Unification

### Status

- Completed.

### Scope And Decisions

- Complete the shared `/projects` action-state contract for confirmations, profile and security forms, backup creation, account deletion, and task editing.
- Use the shared 44px button size and Spinner component for primary form and dialog actions.
- Prevent mutable fields from changing while an Inertia form is processing, including `disable-while-processing` for the account deletion Form component.
- Preserve all routes, payloads, permissions, translations, and success behavior.

### Changed Files

- `resources/js/components/shared/WorkspaceConfirmDialog.vue`
- `resources/js/components/DeleteUser.vue`
- `resources/js/pages/settings/Backup.vue`
- `resources/js/pages/settings/Profile.vue`
- `resources/js/pages/settings/Security.vue`
- `resources/js/pages/tasks/Show.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change was made.

### Verification

- The focused RED run produced four expected failures for missing shared confirmation loading, inert account deletion, incomplete settings processing states, and the local task-edit loader.
- Focused frontend-design coverage passed after implementation with 104 tests and 434 assertions.
- Full Pest coverage passed with 394 tests and 1,758 assertions.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,369 modules.
- Mobile dark browser QA confirmed the Profile delete dialog and Task edit form use 44px controls, readable responsive surfaces, and zero horizontal overflow.
- Desktop light browser QA confirmed the Security password and two-factor actions, Backup create action, and shared restore confirmation use the same 44px action rhythm with zero horizontal overflow.
- Live measurement confirmed 44px profile, security, backup, confirmation, and task-edit actions plus 44px task inputs/selects and account-confirmation inputs.
- The security route's existing password-confirmation guard was completed with the demo password solely to inspect the page; no settings or data were changed.
- The final browser interactions produced no console errors or page errors; the Boost browser log contained only historical entries dated 2026-07-19.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- Compact toolbar and icon actions intentionally retain their smaller semantic sizes; primary form and dialog actions now share the `/projects` 44px contract.

### Git Delivery

- Implementation commit: `aedd8bf` (`fix: unify final asynchronous action states`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `7982a52` (`docs: record final action state unification`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Shared Empty And Secondary Action Unification

### Status

- Completed.

### Scope And Decisions

- Remove the remaining page-local duplication from active empty-state, calendar, task-detail, and import actions.
- Reuse the shared 44px Button and Spinner contracts established by `/projects` while preserving intentionally compact tabs, badges, and icon-only controls.
- Prevent duplicate file-import interactions while an Inertia upload is processing and expose its busy state accessibly.
- Preserve existing routes, payloads, translations, permissions, export behavior, and task behavior.

### Changed Files

- `resources/js/components/shared/EmptyState.vue`
- `resources/js/components/task/TaskDetail.vue`
- `resources/js/pages/calendar/Index.vue`
- `resources/js/pages/settings/Export.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Verification

- The focused RED run produced two expected failures for duplicated large-action styles and the missing import busy state.
- Focused frontend-design coverage passed after implementation with 106 tests and 449 assertions.
- Full Pest coverage passed with 396 tests and 1,773 assertions.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,369 modules.
- Desktop light browser QA confirmed the shared task empty-state action is 44px with the canonical 12px radius and orange treatment, while Export/Import retains coherent 80px action cards, visible keyboard-focus file inputs, and zero horizontal overflow.
- Mobile dark browser QA confirmed the calendar Today action is 44px with the shared 12px radius and zero horizontal overflow at 390x844.
- Task rows continue to resolve to the canonical task show page during live navigation, so the imported task-detail drawer controls are protected by the focused source contract; the task show page was already covered in the preceding browser phase.
- No data-changing import was submitted during visual QA; the upload interlock, disabled state, accessible busy state, shared Spinner, and lifecycle reset are covered by the focused regression contract.
- The final browser interactions produced no console errors or page errors; the Boost browser log contained only historical entries dated 2026-07-19.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- Compact tabs, badges, and icon-only controls intentionally retain their smaller semantic sizes.

### Git Delivery

- Implementation commit: `dea5fca` (`fix: align shared secondary actions`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `72a3b58` (`docs: record secondary action unification`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Page Header Action State Unification

### Status

- Completed.

### Scope And Decisions

- Make every active `WorkspacePageHeader` action use the same shared 44px Button contract as `/projects`.
- Remove page-local orange, height, radius, shadow, and focus duplication from project, task, notification, and workspace headers.
- Add shared Spinner feedback, duplicate-submission guards, disabled states, and lifecycle reset to header-level Inertia mutations.
- Preserve existing routes, payloads, translations, permissions, toast behavior, navigation, and responsive wrapping.

### Changed Files

- `resources/js/pages/notifications/Index.vue`
- `resources/js/pages/projects/Index.vue`
- `resources/js/pages/projects/Show.vue`
- `resources/js/pages/tasks/Index.vue`
- `resources/js/pages/tasks/Show.vue`
- `resources/js/pages/workspaces/Index.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Verification

- The focused RED run produced seven expected failures for six inconsistent page-header button contracts and the missing shared mutation loading states.
- Focused frontend-design coverage passed after implementation with 113 tests and 482 assertions.
- Full Pest coverage passed with 403 tests and 1,806 assertions.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,369 modules.
- Desktop light browser QA confirmed Projects, Workspaces, and Notifications use 44px header actions with the canonical 12px radius and zero horizontal overflow.
- Mobile dark browser QA confirmed Project Show and Task Show wrap their 44px actions across two balanced rows at 390x844 with zero horizontal overflow.
- Live measurements confirmed every rendered header action reports `data-size="lg"`; no page or console errors were produced.
- Data-changing header actions were not submitted during visual QA; duplicate-submission guards, disabled states, shared Spinner feedback, and `onFinish` resets are protected by focused regression contracts.
- The Boost browser log contained only historical entries dated 2026-07-19.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- Compact list-row, tab, badge, and icon-only actions intentionally retain their smaller semantic sizes.

### Git Delivery

- Implementation commit: `8027849` (`fix: unify page header action states`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `15b9f40` (`docs: record page header action unification`).
- Documentation push: successful to `origin/main`.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Shared Segmented Filter Unification

### Status

- Completed.

### Scope And Decisions

- Replace the duplicated project, notification, calendar, and activity segmented filters with shared warm-precision components derived from `/projects`.
- Keep horizontal controls scroll-safe on narrow screens and preserve the activity filter's responsive vertical layout.
- Preserve each page's existing filter state, counts, translations, routes, and interaction behavior while retaining the correct tablist or button-group semantics.
- Add a missing accessible label to the calendar view switcher and keep orange focus, reduced-motion, muted inactive, and card-active states consistent.

### Changed Files

- `resources/js/components/shared/WorkspaceSegmentedControl.vue`
- `resources/js/components/shared/WorkspaceSegmentedButton.vue`
- `resources/js/pages/activity/Index.vue`
- `resources/js/pages/calendar/Index.vue`
- `resources/js/pages/notifications/Index.vue`
- `resources/js/pages/projects/Index.vue`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Migrations And Packages

No migration or Composer/npm package change is planned.

### Verification

- The focused RED run produced the expected failures for four consumers without the shared components, the absent shared component files, and the calendar switcher's missing accessible label.
- Focused segmented-control coverage passed with 11 tests and 37 assertions; the complete frontend-design file passed with 116 tests and 505 assertions.
- Full Pest coverage passed with 406 tests and 1,829 assertions.
- PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- Production build passed after transforming 3,373 modules.
- Desktop light browser QA confirmed Projects uses 40px shared tabs and Activity uses full-width 44px filters; live Archived and Created selections updated both ARIA and visual state with zero page overflow.
- Mobile dark browser QA confirmed Notifications and Calendar use 40px shared tabs, the calendar switcher now exposes the translated `Filters` label, and local state changes updated both ARIA and visual state.
- Activity retained 44px horizontal controls on mobile: its 182px excess width remains contained in the filter's intentional horizontal scroller while document overflow stays at zero.
- Browser QA produced no console errors or page errors; the Boost browser log contained only historical entries dated 2026-07-19.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.

### Known Limitations And Next Work

- Vite continues to report the existing optional `fontaine` optimization notice; the production build succeeds.
- Activity intentionally scrolls its four filters horizontally below the desktop breakpoint, preserving 44px targets without forcing the page wider than the viewport.

### Git Delivery

- Implementation commit: `cf518cc` (`fix: unify segmented filters`).
- Implementation push: successful to `origin/main`.
- Documentation commit: `dfccb7d` (`docs: record segmented filter unification`).
- Documentation push: successful to `origin/main`.
- Unrelated user-authored progress entries remain preserved outside this phase's staged changes.
- This final delivery record is committed and pushed separately so the phase commits remain focused.

## Dashboard Information Correctness Repair

### Status

Completed.

### Scope And Decisions

- Correct dashboard totals, completion metrics, overdue tasks, upcoming tasks, and weekly activity that were accidentally inheriting the first `due_date = today` constraint from a shared mutable builder.
- Move the complex read into `DashboardQuery`, keep every task query scoped to the selected workspace, and keep `DashboardController` limited to authenticated context and response assembly.
- Count all overdue tasks independently from the ten-item display list and collapse the previous per-day weekly count loop into one conditional aggregate query.
- Calculate calendar and timestamp boundaries in the authenticated user's configured timezone, with UTC boundaries for stored timestamps.
- Serialize `due_date` as the date-only `Y-m-d` value so negative-offset timezones cannot shift a displayed deadline to the previous day.
- Preserve the existing Inertia component and prop names, archived-task exclusion, empty-workspace response, and SQLite-only architecture.

### Changed Files

- `app/Http/Controllers/DashboardController.php`
- `app/Models/Todo.php`
- `app/Services/DashboardQuery.php`
- `tests/Feature/DashboardTest.php`
- `docs/progress.md`.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Verification

- The focused RED run failed because `stats.overdue_count` was `0` instead of `1`; the date-only RED run failed because `2026-07-19` serialized as `2026-07-19T00:00:00.000000Z`.
- Focused dashboard coverage passed with 3 tests and 49 assertions.
- Full Pest coverage passed with 407 tests and 1,875 assertions.
- Full PHPStan passed with 0 errors.
- Vue TypeScript checking, ESLint, resource Prettier verification, and the frontend Node test passed.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.
- The production build passed after transforming 3,373 modules; only the existing optional `fontaine` optimization notice remains.
- Live desktop browser QA at `https://xiaomi-mimo.test/dashboard` confirmed totals of 27 tasks, 8 completed, and 3 overdue against SQLite, correct deadline dates such as `Jul 21`, and no console or page errors.

### Known Limitations And Next Work

- The optional `fontaine` notice remains non-blocking and outside this dependency-free repair.
- The dashboard keeps the existing ten-item overdue/upcoming display limits; aggregate overdue totals are no longer capped by those lists.

### Git Delivery

- Implementation commit `275d364` (`fix: correct dashboard information`) was pushed successfully to `origin/main`.
- Documentation commit `a8e3df0` (`docs: record dashboard information repair`) was pushed successfully to `origin/main`.
- This final delivery record will be committed and pushed separately. Existing unrelated `docs/progress.md` changes remain preserved and excluded.

## Dashboard Information Presentation Repair

### Status

Completed.

### Scope And Decisions

- Reconcile the live dashboard with the already-correct workspace-scoped SQLite and Inertia data contract.
- Render the daily task and completion information currently supplied but ignored by `Dashboard.vue`.
- Keep today's tasks and future upcoming tasks in distinct, non-duplicated buckets.
- Reuse the existing productivity component so both completed and created weekly series are presented accessibly.

### Migrations And Packages

No migration or Composer/npm package change was made.

### Changed Files

- `app/Services/DashboardQuery.php`
- `resources/js/pages/Dashboard.vue`
- `resources/js/components/dashboard/ProductivityChart.vue`
- `lang/en/ui.php`
- `lang/lt/ui.php`
- `lang/ru/ui.php`
- `tests/Feature/DashboardTest.php`
- `tests/Feature/FrontendDesignTest.php`
- `docs/progress.md`

### Verification

- Live reproduction confirmed dashboard, task-index, and SQLite totals agree at 27 active tasks, 8 completed tasks, and 3 overdue tasks.
- Source inspection confirmed `today_count`, `completed_today`, `todayTasks`, `completion_rate`, and `weeklyData.created` are supplied but not fully rendered.
- The focused RED run failed because a today task was duplicated in `upcomingTasks` and the Vue page did not render the complete supplied information; the focused GREEN run passed with 5 tests and 56 assertions.
- Dashboard, frontend-design, and localization coverage passed with 123 tests and 591 assertions.
- Full Pest coverage passed with 408 tests and 1,879 assertions.
- PHPStan, Vue TypeScript checking, ESLint, Prettier verification, and the frontend Node test passed.
- `vendor/bin/pint --dirty --format agent` and `git diff --check` passed.
- The production build passed after transforming 3,375 modules; only the existing optional `fontaine` notice remains.
- Live desktop and 390-pixel mobile QA showed the overall completion summary, due-today and completed-today metrics, a distinct today section, future-only upcoming tasks, full years on deadlines, and both weekly completed/created series.
- Browser QA produced zero page errors, console errors, and horizontal overflow; Boost logs contain only historical entries from 2026-07-19.

### Known Limitations And Next Work

- Two existing SQLite tasks have due dates in 1976 and 2000. They are correctly counted as overdue and now display their full year; this phase did not alter or delete user data.
- The existing optional `fontaine` build notice remains non-blocking and outside this dependency-free repair.

### Git Delivery

- Implementation commit `de534f7` (`fix: present complete dashboard information`) was pushed successfully to `origin/main`.
- Documentation commit `b99f915` (`docs: record dashboard presentation repair`) was pushed successfully to `origin/main`.
- Unrelated user-authored progress entries remain preserved outside this phase's staged changes.
- This final delivery record will be committed and pushed separately so the phase commits remain focused.
