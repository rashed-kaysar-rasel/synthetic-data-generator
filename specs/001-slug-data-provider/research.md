# Research: Slug Data Provider Option

## Decision 1: Slug transformation algorithm
- **Decision**: Use a deterministic, in-app slugify routine that lowercases, trims, replaces whitespace with hyphens, removes non-alphanumeric characters except hyphens, and collapses repeated hyphens.
- **Rationale**: Matches FR-004 exactly and avoids locale-specific transliteration while keeping output predictable.
- **Alternatives considered**:
  - Use framework helper `Str::slug` with locale defaults. Rejected because it performs transliteration beyond the specified rules.

## Decision 2: Payload shape for slug source
- **Decision**: Add `slugSourceColumn` (camelCase) on each column configuration entry alongside `provider`.
- **Rationale**: Aligns with existing payload style (`rowCount`) and keeps slug-specific data close to the target column.
- **Alternatives considered**:
  - `sourceColumn` (ambiguous for future provider types).
  - `slug_source_column` (inconsistent with existing camelCase fields).

## Decision 3: Text-like source column eligibility
- **Decision**: Treat data types containing `char`, `text`, `uuid`, or `citext` (case-insensitive) as text-like for slug sources.
- **Rationale**: Covers common MySQL and PostgreSQL string types with a simple, explainable rule.
- **Alternatives considered**:
  - Allow any column type. Rejected because FR-003 requires text-only sources.
  - Maintain a long, database-specific whitelist. Rejected as brittle and harder to maintain.

## Decision 4: Validation placement
- **Decision**: Enforce slug source selection on both client (UX) and server (validation) when provider is `text.slug`.
- **Rationale**: Prevents invalid configurations in the UI and guarantees correctness for API clients.
- **Alternatives considered**:
  - Client-only validation. Rejected due to missing server-side guarantee.
