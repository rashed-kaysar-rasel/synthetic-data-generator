# Tasks: Saved Generation Configurations

**Input**: Design documents from `C:\Projects\synthetic-data-generator\specs\001-saved-config\`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/, quickstart.md

**Tests**: Not requested in the feature specification.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm scope and touchpoints before changes

- [x] T001 Review generator config flow touchpoints in `backend/resources/views/generator/configure.blade.php`, `backend/resources/js/generator.js`, `backend/app/Http/Controllers/DataGenerationController.php`, `backend/routes/web.php`, `backend/resources/views/generator/index.blade.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared configuration used by all stories

**CRITICAL**: No user story work can begin until this phase is complete

- [ ] T002 Add saved configuration model and migration with user-unique name in `backend/app/Models/SavedConfiguration.php` and `backend/database/migrations/`
- [ ] T003 Add schema signature helper in `backend/app/Services/SchemaSignatureService.php`

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - Save Configuration (Priority: P1) ? MVP

**Goal**: Allow logged-in users to save named schema configurations.

**Independent Test**: Configure a schema, save with a unique name, and confirm it appears in the list.

### Implementation for User Story 1

- [ ] T004 [P] [US1] Add save config routes protected by auth in `backend/routes/web.php`
- [ ] T005 [P] [US1] Create saved config controller with store/list actions in `backend/app/Http/Controllers/SavedConfigController.php`
- [ ] T006 [P] [US1] Add save config UI section in `backend/resources/views/generator/configure.blade.php`
- [ ] T007 [US1] Persist full generator payload and schema signature in `backend/app/Http/Controllers/SavedConfigController.php`

**Checkpoint**: User Story 1 fully functional and independently testable

---

## Phase 4: User Story 2 - Import Saved Configuration (Priority: P2)

**Goal**: Allow importing a saved configuration into the generator form.

**Independent Test**: Select a saved configuration and verify form values are populated.

### Implementation for User Story 2

- [ ] T008 [P] [US2] Add import route to fetch a saved config in `backend/routes/web.php`
- [ ] T009 [P] [US2] Add import UI (dropdown/select) in `backend/resources/views/generator/configure.blade.php`
- [ ] T010 [US2] Populate generator form from saved payload in `backend/resources/js/generator.js`
- [ ] T011 [US2] Enforce schema signature strict match before import in `backend/app/Http/Controllers/SavedConfigController.php`

**Checkpoint**: User Story 2 fully functional and independently testable

---

## Phase 5: User Story 3 - Guest Restrictions (Priority: P3)

**Goal**: Hide save/import UI and restrict endpoints for guests.

**Independent Test**: Logged-out user sees no save/import UI on upload or configure pages and cannot access routes.

### Implementation for User Story 3

- [x] T012 [US3] Hide save/import UI for guests in `backend/resources/views/generator/configure.blade.php`
- [x] T013 [US3] Restrict save/import endpoints with auth middleware in `backend/routes/web.php`
- [ ] T014 [US3] Add saved config import UI to upload page for logged-in users in `backend/resources/views/generator/index.blade.php` (REMOVED)

**Checkpoint**: User Story 3 fully functional and independently testable

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Validate documentation and UX alignment

- [x] T015 Update `specs/001-saved-config/quickstart.md` to match final UI behavior

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Foundational - no dependencies on other stories
- **User Story 2 (P2)**: Depends on US1 save data storage
- **User Story 3 (P3)**: Depends on US1 routes/UI definition

---

## Parallel Execution Examples

### User Story 1

- T004 in `backend/routes/web.php`
- T005 in `backend/app/Http/Controllers/SavedConfigController.php`
- T006 in `backend/resources/views/generator/configure.blade.php`

### User Story 2

- T008 in `backend/routes/web.php`
- T009 in `backend/resources/views/generator/configure.blade.php`
- T010 in `backend/resources/js/generator.js`

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Confirm configuration save works

### Incremental Delivery

1. Setup + Foundational
2. User Story 1 ? validate save
3. User Story 2 ? validate import
4. User Story 3 ? validate guest restrictions
5. Polish phase
