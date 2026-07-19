# SQLite

## Support Contract

SQLite is the only supported relational database. Migrations, queries, locking, backup, restore, and operational guidance must remain SQLite-compatible. Database files and WAL files must not be placed on unsupported network filesystems.

## Inspected Runtime

On 2026-07-19 the repository database reported:

- `journal_mode`: `wal`
- `synchronous`: `2` (`FULL`)
- `busy_timeout`: `0`
- `cache_size`: `-2000`
- `temp_store`: `0`
- `wal_autocheckpoint`: `1000`
- `integrity_check`: `ok`
- `foreign_keys`: `0` on the inspected SQLite CLI connection

PRAGMAs are connection-specific, so later work must verify values through Laravel's actual connection as well. Configuration must be environment-driven and applied once per connection, not before every query.

## Known Risks

The current backup service copies the main database file directly and does not establish a WAL-consistent snapshot. Restore accepts a filename-derived path and replaces the database without integrity, compatibility, authorization, or exclusive-operation controls. These workflows must not be treated as production-safe.
