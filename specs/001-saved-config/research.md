# Research: Saved Generation Configurations

## Decision 1: Schema compatibility check
- **Decision**: Compute a deterministic schema signature from the parsed schema (table and column names/types) and store it with each saved configuration.
- **Rationale**: Prevents importing configurations into incompatible schemas.
- **Alternatives considered**:
  - Free-form import with best-effort matching. Rejected due to ambiguous mismatches.

## Decision 2: Storage model
- **Decision**: Persist saved configurations per user in application storage.
- **Rationale**: Configurations must survive sessions and be user-scoped.
- **Alternatives considered**:
  - Client-only storage. Rejected because users need access across sessions.

## Decision 3: Guest visibility
- **Decision**: Hide save/import UI for guests and require auth for API endpoints.
- **Rationale**: Aligns with feature requirements and avoids dead-end actions for guests.
- **Alternatives considered**:
  - Allow guest saving locally. Rejected as out of scope.
