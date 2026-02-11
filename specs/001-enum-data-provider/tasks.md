# Tasks: Enum Data Provider

**Input**: Design documents from `C:\Projects\synthetic-data-generator\specs\001-enum-data-provider\`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/, quickstart.md

**Tests**: Not requested in the feature specification.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm scope and touchpoints before changes

- [x] T001 Review generator flow touchpoints in `backend/resources/views/generator/configure.blade.php`, `backend/resources/js/generator.js`, `backend/app/Http/Controllers/DataGenerationController.php`, `backend/app/Services/DataGeneratorService.php`, `backend/config/data_providers.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared configuration used by all stories

**CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Add `enum` provider option under the `text` group in `backend/config/data_providers.php`

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - Configure Enum Values (Priority: P1) ? MVP

**Goal**: Allow users to select enum and enter allowed values, and generate enum-based output.

**Independent Test**: Select `text.enum` for a column, enter values (comma or newline separated), submit generation, and confirm outputs are drawn from the provided list.

### Implementation for User Story 1

- [x] T003 [P] [US1] Add enum values input UI that appears when provider is `text.enum` in `backend/resources/views/generator/configure.blade.php`
- [x] T004 [P] [US1] Parse enum values input and include `enumValues` in payload in `backend/resources/js/generator.js`
- [x] T005 [US1] Generate enum values from `enumValues` when provider is `text.enum` in `backend/app/Services/DataGeneratorService.php`

**Checkpoint**: User Story 1 fully functional and independently testable

---

## Phase 4: User Story 2 - Prevent Invalid Enum Configuration (Priority: P2)

**Goal**: Block generation when enum is selected without valid values.

**Independent Test**: Select `text.enum` for a column, leave values empty/whitespace, submit generation, and confirm validation error.

### Implementation for User Story 2

- [x] T006 [P] [US2] Add client-side validation for missing enum values when provider is `text.enum` in `backend/resources/js/generator.js`
- [x] T007 [US2] Enforce server-side validation for `text.enum` requiring non-empty values in `backend/app/Http/Controllers/DataGenerationController.php`

**Checkpoint**: User Story 2 fully functional and independently testable

---

## Phase 5: User Story 3 - Edit Enum Values (Priority: P3)

**Goal**: Allow updating enum values and use updated values for generation.

**Independent Test**: Change enum values and re-run generation to verify new list is used.

### Implementation for User Story 3

- [x] T008 [US3] Ensure enum values are re-read from the current UI state on submit in `backend/resources/js/generator.js`

**Checkpoint**: User Story 3 fully functional and independently testable

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Validate documentation and UX alignment

- [x] T009 Update `specs/001-enum-data-provider/quickstart.md` to match final UI behavior

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Foundational - no dependencies on other stories
- **User Story 2 (P2)**: Depends on US1 enum input capture
- **User Story 3 (P3)**: Depends on US1 enum input capture

---

## Parallel Execution Examples

### User Story 1

- T003 in `backend/resources/views/generator/configure.blade.php`
- T004 in `backend/resources/js/generator.js`

### User Story 2

- T006 in `backend/resources/js/generator.js`
- T007 in `backend/app/Http/Controllers/DataGenerationController.php`

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Confirm enum provider selection + value list generation works

### Incremental Delivery

1. Setup + Foundational
2. User Story 1 ? validate UI + payload + generation
3. User Story 2 ? validate client + server errors
4. User Story 3 ? validate editing values
5. Polish phase
