# Domain Model

## Ownership

- A user owns and belongs to workspaces through `workspace_members`.
- A workspace owns projects, todos, labels, tags, and activity logs.
- A todo belongs to a workspace and may belong to a project, assignee, and parent todo.
- A todo owns checklists, comments, reminders, attachments, and subtasks, and has many labels and tags through pivots.
- A checklist owns ordered checklist items.
- User preferences are one-to-one with a user.

## Invariants

Every related project, assignee, parent, label, tag, checklist, reminder user, and attachment must belong to the same authorized workspace context. Bulk operations must reject mixed or foreign identifiers atomically. A task cannot parent itself or create a cycle. The final owner membership cannot be removed, and ownership cannot be silently lost.

## Current Evidence

The schema has workspace IDs on projects, todos, labels, tags, and activity logs, but most cross-record same-workspace invariants are enforced only in application code and are incomplete. Several live tables contain no foreign-key clauses despite migration intent. Prompt 1 must map every relationship, constraint, deletion behavior, index, serializer, and frontend consumer in detail.
