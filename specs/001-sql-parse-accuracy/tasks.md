# Tasks: SQL Parse Accuracy

**Input**: Design documents from `/specs/001-sql-parse-accuracy/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are OPTIONAL and not requested in the specification.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `backend/app/`, `backend/resources/`, `backend/routes/`, `backend/tests/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [X] T001 Review `backend/db_pltte.sql` and document any new constraint patterns in `specs/001-sql-parse-accuracy/research.md`
- [X] T002 Confirm schema fields required by generation are documented in `specs/001-sql-parse-accuracy/data-model.md`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**CRITICAL**: No user story work can begin until this phase is complete

- [X] T003 Add parsing support for column nullability, defaults, and auto-increment flags in `backend/app/Services/SqlParserService.php`
- [X] T004 Add parsing support for primary, unique, and index definitions (CREATE + ALTER) in `backend/app/Services/SqlParserService.php`
- [X] T005 Normalize and merge constraints/indexes into a consistent schema structure in `backend/app/Services/SqlParserService.php`
- [X] T006 Ensure schema stored in session includes constraints/indexes in `backend/app/Http/Controllers/SchemaController.php`

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Import-Ready Generation (Priority: P1) MVP

**Goal**: Generate SQL/CSV output that imports without FK or unique constraint errors.

**Independent Test**: Use `backend/db_pltte.sql`, generate data for all tables, import
into an empty database without FK/unique errors.

### Implementation for User Story 1

- [X] T007 [P] [US1] Build constraint-aware ordering from schema in `backend/app/Services/DataGeneratorService.php`
- [X] T008 [P] [US1] Enforce FK value selection from parent PK pools in `backend/app/Services/DataGeneratorService.php`
- [X] T009 [P] [US1] Enforce unique/primary key generation across rows in `backend/app/Services/DataGeneratorService.php`
- [X] T010 [US1] Ensure generated SQL/CSV uses normalized identifiers from schema in `backend/app/Services/DataGeneratorService.php`

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Real-World Dump Parsing (Priority: P2)

**Goal**: Parse common dump formats (comments, SET/COMMIT, ALTER TABLE) and surface full schema.

**Independent Test**: Upload a dump with comments and deferred constraints; verify all
tables/constraints appear in the configuration UI.

### Implementation for User Story 2

- [X] T011 [P] [US2] Harden statement splitting to ignore comments/transactions in `backend/app/Services/SqlParserService.php`
- [X] T012 [P] [US2] Parse ALTER TABLE constraint blocks for FK/unique/index metadata in `backend/app/Services/SqlParserService.php`
- [X] T013 [US2] Update schema payload to include indexes/uniques for UI use in `backend/app/Http/Controllers/SchemaController.php`
- [X] T014 [US2] Display constraint metadata (PK/FK/Unique/Not Null) in `backend/resources/views/generator/configure.blade.php`

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Constraint-Driven Failure Feedback (Priority: P3)

**Goal**: Provide clear errors when requested row counts cannot satisfy constraints.

**Independent Test**: Request more rows than a unique constraint can support and see
an actionable error without generating invalid output.

### Implementation for User Story 3

- [X] T015 [P] [US3] Add pre-generation constraint validation in `backend/app/Http/Controllers/DataGenerationController.php`
- [X] T016 [US3] Return actionable validation errors to the UI in `backend/resources/js/generator.js`
- [X] T017 [US3] Add user-facing error messaging for constraint violations in `backend/resources/views/generator/configure.blade.php`

**Checkpoint**: All user stories should now be independently functional

---

## Phase N: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T018 [P] Update usage notes in `specs/001-sql-parse-accuracy/quickstart.md` with validation checklist
- [ ] T019 Run the quickstart validation steps in `specs/001-sql-parse-accuracy/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 -> P2 -> P3)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - May integrate with US1 but should be independently testable
- **User Story 3 (P3)**: Can start after Foundational (Phase 2) - May integrate with US1/US2 but should be independently testable

### Within Each User Story

- Schema parsing improvements before generation changes
- Generation logic before UI feedback changes

### Parallel Opportunities

- T003 and T004 can run in parallel (same file but different sections)
- T007, T008, T009 can run in parallel (same file but distinct functions)
- T011 and T012 can run in parallel (same file but distinct concerns)

---

## Parallel Example: User Story 1

```bash
# Parallel constraint enforcement work:
Task: "Enforce FK value selection from parent PK pools in backend/app/Services/DataGeneratorService.php"
Task: "Enforce unique/primary key generation across rows in backend/app/Services/DataGeneratorService.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently

### Incremental Delivery

1. Complete Setup + Foundational -> Foundation ready
2. Add User Story 1 -> Test independently -> Deploy/Demo (MVP!)
3. Add User Story 2 -> Test independently -> Deploy/Demo
4. Add User Story 3 -> Test independently -> Deploy/Demo
5. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1
   - Developer B: User Story 2
   - Developer C: User Story 3
3. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
