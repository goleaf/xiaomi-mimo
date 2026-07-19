# Authorization

## Required Matrix

The existing `WorkspaceRole` enum remains the source of role values: owner, admin, and member. The detailed permission matrix will be finalized from product requirements during the authorization phase.

Baseline rules are:

| Capability | Owner | Admin | Member |
| --- | --- | --- | --- |
| View workspace and member content | Yes | Yes | Yes |
| Update workspace settings | Yes | Yes | No |
| Delete workspace | Yes | No | No |
| Invite and manage non-owner members | Yes | Yes | No |
| Transfer ownership or remove final owner | Controlled | No | No |
| Create and update tasks/projects | Yes | Yes | Yes |
| Administrative data transfer and backup | Yes | To be decided | No |

## Current Risk

Several policies use membership as the only write criterion. Label and tag controllers have paths with no explicit policy call, related IDs are often validated globally, and nested bindings are not consistently scoped. Frontend visibility is not authorization. Prompt 2 must implement and test the complete matrix.
