# API

## Current State

`routes/api.php` exposes unversioned `/api` endpoints protected primarily by `auth:sanctum`, plus public token login and registration. API controllers duplicate parts of the regular controller layer. Resources are used inconsistently: item responses may be nested under domain names while resource responses use `data`, and validation/authentication errors have no documented machine-readable contract.

## Target Contract

- External endpoints live under `/api/v1`.
- Web session authentication and token authentication remain distinct.
- Tokens receive documented, least-privilege abilities.
- Both web and API presentations call the same actions and query objects.
- Item responses use `data` with optional `meta`; collections include data, links, and pagination meta.
- Errors include a stable code, localized message, validation fields where applicable, and request identifier.

Backward compatibility and deprecation behavior must be decided from observed consumers during prompt 3.
