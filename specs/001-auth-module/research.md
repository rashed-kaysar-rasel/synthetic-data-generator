# Research: Authentication Module

## Decision 1: Authentication method
- **Decision**: Use standard session-based authentication with email/password.
- **Rationale**: Aligns with current server-rendered Blade UI and Laravel defaults.
- **Alternatives considered**:
  - Token-only API auth. Rejected because UI is server-rendered and needs session support.

## Decision 2: Password reset approach
- **Decision**: Use time-limited reset tokens and a multi-step reset flow.
- **Rationale**: Meets expected security practices and recovery needs.
- **Alternatives considered**:
  - Manual admin resets. Rejected because it does not scale.

## Decision 3: Guest access scope
- **Decision**: Keep existing generator routes accessible without authentication; restrict only new account pages to auth middleware where appropriate.
- **Rationale**: Preserves current user experience while adding authentication.
- **Alternatives considered**:
  - Require login for all usage. Rejected due to explicit requirement for guest access.
