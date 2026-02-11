# Tasks: Slug Data Provider Option

**Input**: Design documents from `C:\Projects\synthetic-data-generator\specs\001-slug-data-provider\`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Not requested in the feature specification.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm scope and touchpoints before changes

- [x] T001 Review generator flow touchpoints in `backend/resources/views/generator/configure.blade.php`, `backend/resources/js/generator.js`, `backend/app/Http/Controllers/DataGenerationController.php`, `backend/app/Services/DataGeneratorService.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared configuration used by all stories

**?? CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Add `slug` provider option under the `text` group in `backend/config/data_providers.php`

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - Choose Slug Type with Source Column (Priority: P1) ?? MVP

**Goal**: Allow users to select a slug provider and choose a source column, with required validation before saving.

**Independent Test**: Select `text.slug` for a column, choose a source column, submit generation, and confirm the request payload includes `slugSourceColumn` and server validation blocks missing source selection.

### Implementation for User Story 1

- [x] T003 [P] [US1] Add slug source column selector UI tied to provider selection in `backend/resources/views/generator/configure.blade.php`
- [x] T004 [P] [US1] Populate slug source options per table and include `slugSourceColumn` in payload in `backend/resources/js/generator.js`
- [x] T005 [P] [US1] Enforce server-side validation for `text.slug` provider and text-like source columns in `backend/app/Http/Controllers/DataGenerationController.php`

**Checkpoint**: User Story 1 fully functional and independently testable

---

## Phase 4: User Story 2 - Slug Values Generated from Source (Priority: P2)

**Goal**: Generate slug values from the selected source column using the defined transformation rules.

**Independent Test**: Generate a row where the source value is known (e.g., `My First Course`) and confirm slug output is `my-first-course`.

### Implementation for User Story 2

- [x] T006 [US2] Add slugify routine that matches FR-004 in `backend/app/Services/DataGeneratorService.php`
- [x] T007 [US2] Use `slugSourceColumn` during row generation when provider is `text.slug` in `backend/app/Services/DataGeneratorService.php`

**Checkpoint**: User Story 2 fully functional and independently testable

---

## Phase 5: User Story 3 - Handle Missing Source Values (Priority: P3)

**Goal**: Ensure empty or null source values produce empty slug outputs.

**Independent Test**: Generate rows with empty/null source values and confirm slug output is empty.

### Implementation for User Story 3

- [x] T008 [US3] Return empty slug when source value is null/empty in `backend/app/Services/DataGeneratorService.php`

**Checkpoint**: User Story 3 fully functional and independently testable

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Validate documentation and UX alignment

- [x] T009 Validate and update `specs/001-slug-data-provider/quickstart.md` to match final UI behavior

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Foundational - no dependencies on other stories
- **User Story 2 (P2)**: Starts after Foundational - independent from US1
- **User Story 3 (P3)**: Depends on US2 slug generation flow

---

## Parallel Execution Examples

### User Story 1

- T003 in `backend/resources/views/generator/configure.blade.php`
- T004 in `backend/resources/js/generator.js`
- T005 in `backend/app/Http/Controllers/DataGenerationController.php`

### User Story 2

- T006 and T007 are sequential in `backend/app/Services/DataGeneratorService.php`

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Confirm slug provider selection + source column requirement works

### Incremental Delivery

1. Setup + Foundational
2. User Story 1 ? validate UI + payload + validation
3. User Story 2 ? validate slug output rules
4. User Story 3 ? validate empty source behavior
5. Polish phase
