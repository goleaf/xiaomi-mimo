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

Commit and push results are pending. Unrelated staged and unstaged application work remains untouched and excluded.
