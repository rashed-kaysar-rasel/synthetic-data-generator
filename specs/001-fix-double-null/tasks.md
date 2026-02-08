# Tasks: Fix Double Type Generation

**Branch**: `001-fix-double-null`  
**Date**: 2026-02-08  
**Plan**: `specs/001-fix-double-null/plan.md`  
**Spec**: `specs/001-fix-double-null/spec.md`

## Phase 1: Setup

- [x] T001 Review current numeric value generation paths in backend/app/Services/DataGeneratorService.php

## Phase 2: Foundational

- [x] T002 Identify how schema data types map to default values for numeric columns in backend/app/Services/DataGeneratorService.php

## Phase 3: User Story 1 (P1) - Generate Numeric Data Correctly

**Goal**: Ensure double columns receive numeric values and respect nullability rules.

**Independent Test**: Run a generation job with a schema containing double columns and verify numeric values are produced per nullability.

- [x] T003 [US1] Update default value generation to treat double as numeric in backend/app/Services/DataGeneratorService.php
- [x] T004 [US1] Ensure nullable double columns only return null when schema allows it in backend/app/Services/DataGeneratorService.php
- [x] T005 [US1] Add/adjust numeric fallback logic for double columns in backend/app/Services/DataGeneratorService.php

## Phase 4: User Story 2 (P2) - Preserve Job Reliability

**Goal**: Ensure jobs complete without double-related failures.

**Independent Test**: Run a generation job that previously produced null double values and confirm it completes without errors.

- [x] T006 [US2] Verify data generation job flow handles double values consistently in backend/app/Services/DataGeneratorService.php

## Phase 5: Polish & Cross-Cutting Concerns

- [x] T007 Add quick sanity check steps to specs/001-fix-double-null/quickstart.md
- [x] T008 [P] Validate generated SQL/CSV output formatting remains correct for double values in backend/app/Services/DataGeneratorService.php

## Dependencies

- User Story 1 must be completed before User Story 2.

## Parallel Execution Examples

- T008 can run in parallel after T003-T005 complete because it only validates output formatting.

## Implementation Strategy

- Deliver MVP by completing Phase 3 (User Story 1) to fix the double generation defect.
- Follow with Phase 4 to confirm job reliability.
- Finish with Phase 5 for documentation and validation.
