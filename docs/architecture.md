# Architecture

## Supported Stack

Laravel owns routing, authentication, authorization, validation, persistence, and server rendering through Inertia. Vue 3 pages use the Composition API and TypeScript. Pinia is reserved for genuinely shared client state. Wayfinder is the target route integration. SQLite is the only supported relational database.

## Target Boundaries

- Web page controllers return Inertia responses or redirects.
- API controllers return versioned resources and consistent error envelopes.
- Form Requests authorize and validate every write and complex filter.
- Actions implement state changes and transactions.
- Query objects implement complex, workspace-scoped reads and eager loading.
- Policies implement the documented role matrix.
- Resources transform already-loaded data and never execute queries.
- Vue pages coordinate focused feature components without mutating Inertia props.

## Current Gaps

The current code only partially follows these boundaries. Regular and API controllers duplicate operations; some controllers mix Inertia and JSON; route closures execute domain queries and invoke controllers; several write paths use inline validation or direct model updates; response envelopes vary; and the frontend mixes generated Wayfinder imports, a custom global `route()`, and hardcoded URLs.

These gaps are recorded, not repaired, by the baseline phase.
