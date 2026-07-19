# Testing

## Existing Suite

The repository uses Pest 4 with feature coverage for authentication, workspaces, projects, todos, checklists, comments, labels/tags, dashboard/activity, settings, and the unversioned API. The suite is primarily backend HTTP coverage; no dedicated Vue component or browser accessibility suite was found.

## Phase Gate

Every implementation phase must add or update focused Pest tests, run the smallest relevant subset first, and then run:

```text
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan test --compact
npm run types:check
npm run lint:check
npm run format:check
npm run build
```

Documentation-only phases do not require fabricated behavior tests, but must still run applicable repository checks and report existing failures without deleting tests.

## Priority Gaps

Cross-workspace attacks, role permutations, mixed-ID bulk operations, nested ownership, backup/restore, import rollback, attachment security, recurrence, reminders, localization, accessibility-critical Vue behavior, query counts, and concurrent SQLite writes need explicit coverage.
