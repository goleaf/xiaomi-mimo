# Security

## Security Boundaries

- Workspace ownership and membership scope all domain data.
- Authorization applies on every state change and private download.
- Related identifiers are reloaded through authorized parent relationships.
- Fortify handles web authentication; Sanctum handles session/API authentication as configured.
- Upload, import, export, backup, and restore inputs receive strict size, type, path, and content validation.

## Baseline Findings

The inspection found high-risk patterns requiring dedicated phases: globally scoped bulk updates/deletes, globally scoped related-ID validation, inconsistent nested binding, incomplete policy coverage, unrestricted token creation, original-extension upload naming, backup path disclosure and filename interpolation, direct WAL database copying, unbounded import parsing, and insufficient data-transfer authorization.

This document does not certify the application as production-secure. Prompt 1 will produce the evidence-based security audit; prompts 2 and 9 implement the prioritized corrections.
