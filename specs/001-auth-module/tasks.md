# Tasks: Authentication Module

**Input**: Design documents from `C:\Projects\synthetic-data-generator\specs\001-auth-module\`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/, quickstart.md

**Tests**: Not requested in the feature specification.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm scope and touchpoints before changes

- [x] T001 Review auth, guest access, and profile touchpoints in `backend/routes/web.php`, `backend/app/Http/Controllers/`, `backend/resources/views/`, `backend/resources/js/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared configuration used by all stories

**CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Confirm user model and authentication scaffolding approach in `backend/app/Models/User.php` and `backend/config/auth.php`

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - Register And Log In (Priority: P1) ? MVP

**Goal**: Enable user registration and login with name, email, and password.

**Independent Test**: Register a user, log in, and confirm authenticated session state.

### Implementation for User Story 1

- [x] T003 [P] [US1] Add registration and login routes in `backend/routes/web.php`
- [x] T004 [P] [US1] Create auth controllers for register/login/logout in `backend/app/Http/Controllers/Auth/`
- [x] T005 [P] [US1] Add registration and login Blade views in `backend/resources/views/auth/`
- [x] T006 [US1] Wire form handling, validation, and session login in `backend/app/Http/Controllers/Auth/`

**Checkpoint**: User Story 1 fully functional and independently testable

---

## Phase 4: User Story 2 - Reset Password (Priority: P2)

**Goal**: Provide password reset flow with secure tokens.

**Independent Test**: Request a reset and successfully set a new password.

### Implementation for User Story 2

- [x] T007 [P] [US2] Add password reset routes in `backend/routes/web.php`
- [x] T008 [P] [US2] Create password reset controllers in `backend/app/Http/Controllers/Auth/`
- [x] T009 [P] [US2] Add password reset Blade views in `backend/resources/views/auth/`
- [x] T010 [US2] Implement token validation and password update flow in `backend/app/Http/Controllers/Auth/`

**Checkpoint**: User Story 2 fully functional and independently testable

---

## Phase 5: User Story 3 - Guest Access To Generation (Priority: P3)

**Goal**: Ensure generator routes remain accessible to unauthenticated users.

**Independent Test**: Access generator while logged out and run a job successfully.

### Implementation for User Story 3

- [x] T011 [US3] Verify generator routes remain outside auth middleware in `backend/routes/web.php`
- [x] T012 [US3] Adjust any middleware protection that blocks guest generation in `backend/app/Http/Controllers/DataGenerationController.php`

**Checkpoint**: User Story 3 fully functional and independently testable

---

## Phase 6: User Story 4 - Profile Management (Priority: P4)

**Goal**: Allow logged-in users to update name, email, and password from a profile page.

**Independent Test**: Log in, update profile fields, and confirm changes persist and re-authentication works.

### Implementation for User Story 4

- [x] T013 [P] [US4] Add profile routes protected by auth middleware in `backend/routes/web.php`
- [x] T014 [P] [US4] Create profile controller in `backend/app/Http/Controllers/Auth/ProfileController.php`
- [x] T015 [P] [US4] Add profile Blade view in `backend/resources/views/auth/profile.blade.php`
- [x] T016 [US4] Implement validation and update logic for name/email/password in `backend/app/Http/Controllers/Auth/ProfileController.php`

**Checkpoint**: User Story 4 fully functional and independently testable

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Validate documentation and UX alignment

- [x] T017 Update `specs/001-auth-module/quickstart.md` to match final UI behavior

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
- **Polish (Phase 7)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Starts after Foundational - no dependencies on other stories
- **User Story 2 (P2)**: Depends on US1 authentication setup
- **User Story 3 (P3)**: Depends on US1 routes/middleware definition
- **User Story 4 (P4)**: Depends on US1 authentication setup

---

## Parallel Execution Examples

### User Story 1

- T003 in `backend/routes/web.php`
- T004 in `backend/app/Http/Controllers/Auth/`
- T005 in `backend/resources/views/auth/`

### User Story 2

- T007 in `backend/routes/web.php`
- T008 in `backend/app/Http/Controllers/Auth/`
- T009 in `backend/resources/views/auth/`

### User Story 4

- T013 in `backend/routes/web.php`
- T014 in `backend/app/Http/Controllers/Auth/ProfileController.php`
- T015 in `backend/resources/views/auth/profile.blade.php`

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Confirm registration + login works

### Incremental Delivery

1. Setup + Foundational
2. User Story 1 ? validate register/login/logout
3. User Story 2 ? validate password reset
4. User Story 3 ? validate guest generation
5. User Story 4 ? validate profile updates
6. Polish phase
