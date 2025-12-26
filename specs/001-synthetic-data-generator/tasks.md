# Tasks: Web-Based Synthetic Data Generator

**Input**: Design documents from `specs/001-synthetic-data-generator/`
**Prerequisites**: plan.md (required), spec.md (required for user stories)

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Web app (Laravel + Blade)**: `app/`, `routes/`, `resources/views/`, `resources/js/`, `tests/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [X] T001 [P] Create a new Laravel 12 project
- [X] T002 [P] Configure Vite with Tailwind CSS for Blade views
- [X] T003 [P] Install Composer dependency `php-sql-parser`
- [X] T004 [P] Configure `.env` file with database credentials

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

- [X] T005 [P] Create the main layout in `resources/views/layouts/app.blade.php`
- [X] T006 [P] Create the main controllers `SchemaController.php` and `DataGenerationController.php`
- [X] T007 [P] Create service stubs: `SqlParserService.php`, `TopologicalSortService.php`, `DataGeneratorService.php` in `app/Services/`
- [X] T008 Define the primary routes (`/`, `/schema`, `/configure`, `/generate`, `/jobs/{id}`, `/download/{file}`) in `routes/web.php`
- [X] T009 Create the upload view in `resources/views/generator/index.blade.php`

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Schema Upload and Visualization (Priority: P1) MVP

**Goal**: Allow a user to upload a SQL file and see the parsed schema.

**Independent Test**: A user can upload a valid SQL file from the main page and see a visualization of the tables and columns on the `/configure` page.

### Implementation for User Story 1

- [X] T010 [P] [US1] Build the file upload form in `resources/views/generator/index.blade.php`
- [X] T011 [US1] Implement the `store` method in `app/Http/Controllers/SchemaController.php` to handle file upload
- [X] T012 [US1] Implement the `SqlParserService` in `app/Services/SqlParserService.php` to parse the uploaded file content using `php-sql-parser`
- [X] T013 [US1] Implement the `TopologicalSortService` in `app/Services/TopologicalSortService.php` to order the parsed tables
- [X] T014 [US1] Store the parsed and sorted schema in the user's session
- [X] T015 [US1] Create the configuration view `resources/views/generator/configure.blade.php`
- [X] T016 [US1] Implement the `show` method in `app/Http/Controllers/SchemaController.php` to display the configure view with schema data from the session
- [X] T017 [US1] Build the UI to display tables, columns, and relationships in `resources/views/generator/configure.blade.php`

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Generation Configuration (Priority: P2)

**Goal**: Allow a user to configure data generation rules for the visualized schema.

**Independent Test**: On the `/configure` page, a user can assign a data provider to a column and set the number of rows for a table. The configuration is stored in the page state.

### Implementation for User Story 2

- [X] T018 [P] [US2] Create a configuration file `config/data_providers.php` to define the list of available fake data providers
- [X] T019 [P] [US2] Add provider selection inputs in `resources/views/generator/configure.blade.php`
- [X] T020 [P] [US2] Add row count inputs in `resources/views/generator/configure.blade.php`
- [X] T021 [US2] Implement client-side config handling in `resources/js/generator.js`

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Data Generation and Download (Priority: P3)

**Goal**: Generate and download the synthetic data based on the user's configuration.

**Independent Test**: A user can click "Generate", the job runs in the background, and a download link appears when complete.

### Implementation for User Story 3

- [X] T022 [P] [US3] Create the `GenerateDataJob` in `app/Jobs/GenerateDataJob.php`
- [X] T023 [P] [US3] Implement the `store` method in `app/Http/Controllers/DataGenerationController.php` to validate the configuration and dispatch the `GenerateDataJob`
- [X] T024 [US3] Implement the core logic in `app/Services/DataGeneratorService.php` to generate data based on the `GenerationConfig` using PHP Generators for memory efficiency
- [X] T025 [US3] Add logic to `DataGeneratorService` to format the output as SQL INSERT statements
- [X] T026 [US3] Add logic to `DataGeneratorService` to format the output as CSV and package it into a ZIP file
- [X] T027 [US3] Implement the job status endpoint in `DataGenerationController@show`
- [X] T028 [US3] Implement the download endpoint in `DataGenerationController@download`
- [X] T029 [US3] Add a "Generate" button and form to the configure view
- [X] T030 [US3] Add frontend logic in `resources/js/generator.js` to submit config and poll job status

**Checkpoint**: All user stories should now be independently functional

---

## Phase N: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T031 [P] Implement detailed validation for the `GenerationConfig` on the backend
- [X] T032 [P] Implement UI for displaying job failure messages with retry option
- [X] T033 Refine UI/UX with Tailwind CSS styling
- [X] T034 Add comprehensive comments to service classes
- [X] T035 Write the project `README.md` file with setup and usage instructions

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately.
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories.
- **User Stories (Phase 3+)**: All depend on Foundational phase completion.
  - User stories can then proceed in parallel if staffed, or sequentially in priority order (P1 -> P2 -> P3).

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2). No dependencies on other stories.
- **User Story 2 (P2)**: Depends on User Story 1. The configuration UI is an extension of the visualization UI.
- **User Story 3 (P3)**: Depends on User Story 2. Generation requires a complete configuration.

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently.

### Incremental Delivery

1. Complete Setup + Foundational -> Foundation ready
2. Add User Story 1 -> Test independently -> MVP is ready
3. Add User Story 2 -> Test independently
4. Add User Story 3 -> Test independently -> Full feature is ready
5. Complete Polish phase

---

## Notes

- [P] tasks = can be run in parallel with other tasks in the same phase.
- [Story] label maps task to a specific user story for traceability.
- Each user story should be independently completable and testable where possible.
