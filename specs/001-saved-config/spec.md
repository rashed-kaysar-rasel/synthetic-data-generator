# Feature Specification: Saved Generation Configurations

**Feature Branch**: `001-saved-config`  
**Created**: 2026-02-11  
**Status**: Draft  
**Input**: User description: "now i want to implement a new feature for logged in users. Users can save the database table configuration and import their saved configuration. For example, user uploaded a database and he configgerd some table to generate data by setting the value providers. Now user can not save the configuration. every time he needs to enter the row number, value provider. But now user will be able to save the configuration"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Save Configuration (Priority: P1)

As a logged-in user, I want to save my current schema configuration so that I can reuse it later without re-entering all row counts and providers.

**Why this priority**: Saving configurations is the core value of the feature and removes repeated setup work.

**Independent Test**: Can be fully tested by configuring a schema, saving it, and confirming it appears in the saved list.

**Acceptance Scenarios**:

1. **Given** a logged-in user with a configured schema, **When** they save the configuration with a unique name, **Then** it is stored and listed for that user.
2. **Given** a logged-in user, **When** they attempt to save without required fields (e.g., name), **Then** a clear validation error is shown.
3. **Given** a logged-in user with an existing configuration name, **When** they try to save using the same name, **Then** they receive a validation error to use a different name.

---

### User Story 2 - Import Saved Configuration (Priority: P2)

As a logged-in user, I want to import a saved configuration so that the generator form is pre-filled with my saved settings.

**Why this priority**: Importing saved configurations completes the reuse workflow.

**Independent Test**: Can be fully tested by selecting a saved configuration and confirming form values are populated.

**Acceptance Scenarios**:

1. **Given** a logged-in user with a saved configuration, **When** they select it to import, **Then** the generator form fields are populated with the saved values.
2. **Given** a saved configuration created for a different schema, **When** the user tries to import it, **Then** the import is blocked and they receive a clear error.

---

### User Story 3 - Guest Restrictions (Priority: P3)

As a guest user, I want to continue generating data without seeing save/import options so that the UI remains simple for guests.

**Why this priority**: Guest access must remain unchanged and avoid showing unavailable actions.

**Independent Test**: Can be fully tested by visiting the generator while logged out and confirming save/import options are hidden.

**Acceptance Scenarios**:

1. **Given** a logged-out visitor, **When** they use the generator, **Then** save/import options are not shown and generation still works.

---

### Edge Cases

- What happens when a user tries to save a configuration with a name that already exists for them? (Show validation error.)
- How does the system handle importing a configuration when some columns no longer exist in the current schema? (Block import.)
- What happens when a saved configuration has providers that are no longer available?
- How does the system handle very large configuration payloads?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow logged-in users to save a named configuration of the entire schema settings.
- **FR-002**: System MUST store saved configurations per user.
- **FR-003**: System MUST allow logged-in users to list and select their saved configurations.
- **FR-004**: System MUST import a saved configuration and pre-fill row counts and providers.
- **FR-005**: System MUST prevent importing configurations that do not match the current schema.
- **FR-006**: System MUST hide save/import options from guest users.
- **FR-007**: System MUST validate required fields when saving (e.g., configuration name).
- **FR-008**: System MUST show user-friendly errors when save or import fails.
- **FR-009**: System MUST enforce unique configuration names per user and reject duplicates.
- **FR-010**: System MUST block imports when the schema signature does not match.
- **FR-011**: System MUST require authentication to save or import configurations.
- **FR-012**: System MUST store the full generator payload for saved configurations.

### Key Entities *(include if feature involves data)*

- **SavedConfiguration**: Named configuration data linked to a user and a schema signature.
- **SchemaSignature**: A derived identifier for a schema used to ensure compatibility during import.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Logged-in users can save a configuration and see it in their list in under 2 minutes.
- **SC-002**: 100% of imported configurations that match the schema populate the generator form correctly in testing.
- **SC-003**: 100% of guest users can generate data without seeing save/import options.
- **SC-004**: Failed imports provide clear, actionable error messages.

## Scope

### In Scope

- Save and import configurations for logged-in users.
- Schema compatibility checks when importing.
- UI for save/import actions for authenticated users only.

### Out of Scope

- Sharing configurations between users.
- Version history of configurations.
- Auto-save or draft configurations.

## Dependencies

- Requires authentication for save/import actions.
- Uses current generator configuration payload structure.

## Assumptions

- A schema signature can be derived from the parsed schema in a deterministic way.
- Saved configurations are scoped to the authenticated user only.

## Clarifications

### Session 2026-02-11

- Q: Should configuration names be unique per user? ? A: Enforce unique names per user; show validation error and ask for a different name.
- Q: How should schema mismatch on import be handled? ? A: Block import when schema signature does not match.
- Q: Should saved configurations cover all tables or per-table? ? A: Save entire schema configuration (all tables at once).
- Q: Should guests be able to save/import configurations? ? A: Require login to save/import (no guest save/import).
- Q: What data should be stored for a saved configuration? ? A: Store the full generator payload.
