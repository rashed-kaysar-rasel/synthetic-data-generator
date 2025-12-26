<!--
Sync Impact Report
- Version change: N/A -> 1.0.0
- Modified principles: N/A (new constitution)
- Added sections: Core Principles, Technical Constraints, Development Workflow, Governance
- Removed sections: None
- Templates requiring updates:
  - ✅ .specify/templates/plan-template.md (no change needed)
  - ✅ .specify/templates/spec-template.md (no change needed)
  - ✅ .specify/templates/tasks-template.md (no change needed)
  - ⚠️ .specify/templates/commands/*.md (folder not present)
  - ✅ README.md (updated)
  - ✅ GEMINI.md (updated)
- Follow-up TODOs:
  - TODO(RATIFICATION_DATE): Original adoption date not recorded; confirm once known.
-->
# Synthetic Data Generator Constitution

## Core Principles

### I. Schema-First Workflow
All generation flows MUST begin with a user-provided SQL DDL schema. The system
MUST derive configuration options from the parsed schema and MUST not allow
generation without a valid schema.

### II. Relational Integrity
Generated data MUST respect primary and foreign key relationships. Table order
MUST be derived from dependency sorting, and foreign key values MUST be sampled
from generated parent rows.

### III. Synthetic Data Safety
Only synthetic data MAY be generated or stored. The system MUST NOT ingest or
reuse real user data, and defaults MUST use built-in fake data providers.

### IV. Transparent Job Feedback
Background generation jobs MUST expose clear status (pending, completed, failed)
and actionable errors. Users MUST be able to retry a failed job without losing
their configuration.

### V. Blade-First Frontend
The frontend MUST use Blade templates with HTML, Tailwind CSS, and vanilla
JavaScript. SPA frameworks (React, Inertia.js) and component libraries tied to
those frameworks are not permitted.

## Technical Constraints

- Backend MUST remain a Laravel application.
- Frontend MUST be rendered server-side with Blade.
- Styling MUST use Tailwind CSS.
- Frontend behavior MUST use vanilla JavaScript and fetch APIs.

## Development Workflow

- Specs and plans MUST include a Constitution Check before implementation.
- Changes that violate a principle MUST include explicit justification and an
  approved exception in the plan.
- Keep UI changes accessible and responsive; avoid framework-specific patterns
  that conflict with Blade rendering.

## Governance

- This constitution is the highest-level project guideline and supersedes other
  docs when conflicts arise.
- Amendments MUST update this file, include a version bump, and update dependent
  templates and runtime guidance files.
- Compliance MUST be reviewed in specs and plans for each feature.

**Version**: 1.0.0 | **Ratified**: TODO(RATIFICATION_DATE): Original adoption date not recorded. | **Last Amended**: 2025-12-26
