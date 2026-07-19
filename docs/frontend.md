# Frontend

## Current Architecture

Vue 3 Inertia pages live in `resources/js/pages`; shared and feature components live in `resources/js/components`; reusable behavior lives in composables; Pinia stores exist for todos, projects, workspaces, notifications, and UI state. The UI uses Reka-based shadcn-style components, Lucide icons, and Tailwind CSS 4.

## Verified Gaps

- Task index board mode does not render `BoardView.vue`.
- The task pages use hardcoded English text and `en-US` formatting.
- A global custom `route()` declaration and helper coexist with generated Wayfinder functions.
- Hardcoded settings and notification URLs remain.
- Local refs initialized from props are not consistently synchronized.
- One checklist-item input ref is shared across multiple checklists.
- Native drag events exist while `@dnd-kit` packages/import patterns remain unresolved.
- Loading, validation, failure, empty, mobile, keyboard, and accessible dialog/drawer states are incomplete.

Frontend phases must preserve the existing design system and use typed props/emits without `any` or broad casts as escape hatches.
