# Deployment

## Requirements

- PHP 8.3 or newer with SQLite/PDO SQLite extensions.
- A writable local filesystem for the SQLite database, WAL sidecar, storage, cache, sessions, uploads, and non-public backups.
- Node.js tooling for the production Vite build.
- Scheduler execution for reminders, recurring tasks, backups, and activity cleanup until those workflows document alternate execution paths.

## Constraints

SQLite is the only supported relational database. Do not place the database or WAL files on unsupported network filesystems. Production deployment must validate the configured database path and directory permissions and must not silently create a database at an unintended relative path.

The current backup/restore implementation is not approved for production use. Deployment guidance will be completed after the SQLite, recurring-operation, and data-transfer phases.
