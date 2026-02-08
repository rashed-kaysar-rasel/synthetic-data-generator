# Feature Specification: Fix Double Type Generation

**Feature Branch**: `001-fix-double-null`  
**Created**: 2026-02-08  
**Status**: Draft  
**Input**: User description: "if the db coloum type is double, it generating null for that column. fix it"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Generate Numeric Data Correctly (Priority: P1)

As a user generating synthetic data, I need columns defined as double to receive valid numeric values so that my generated dataset is complete and usable.

**Why this priority**: Missing numeric values break downstream workflows and invalidate generated datasets.

**Independent Test**: Run a generation job with a schema that includes at least one double column and verify the resulting output contains valid numeric values in that column.

**Acceptance Scenarios**:

1. **Given** a schema with a non-nullable double column, **When** a generation job completes, **Then** every generated row has a numeric value for that column.
2. **Given** a schema with a nullable double column, **When** a generation job completes, **Then** the column contains numeric values except where nulls are explicitly allowed.

---

### User Story 2 - Preserve Job Reliability (Priority: P2)

As a user running generation jobs, I need jobs to complete without errors related to double columns so that I can reliably produce datasets.

**Why this priority**: Job failures slow users down and require manual retries.

**Independent Test**: Run a generation job that previously produced null double values and confirm it completes without errors or missing values.

**Acceptance Scenarios**:

1. **Given** a schema that includes double columns, **When** a generation job runs, **Then** the job completes successfully without double-related failures.

---

### Edge Cases

- What happens when a double column is nullable and a user expects some values to be empty?
- How does the system handle extremely small or large double values within typical numeric ranges?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST generate numeric values for columns whose data type is double.
- **FR-002**: System MUST NOT generate null values for non-nullable double columns.
- **FR-003**: System MUST allow null values for double columns only when the schema permits nulls.
- **FR-004**: System MUST complete generation jobs without errors attributable to double column handling.
- **FR-005**: Users MUST be able to obtain generated datasets where double columns are populated consistently across rows.

### Key Entities *(include if feature involves data)*

- **Schema Column**: A column definition including data type and nullability.
- **Generated Row**: A single output record produced during data generation.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of values in non-nullable double columns are numeric after a generation job completes.
- **SC-002**: Generation jobs with double columns complete successfully in a single run without user intervention.
- **SC-003**: User-reported issues about missing values in double columns drop to zero for this defect category.

## Scope

### In Scope

- Correct generation behavior for columns defined as double.

### Out of Scope

- Changes to generation behavior for other numeric types unless necessary for consistency.

## Dependencies

- The generation job receives accurate schema metadata for column data types and nullability.

## Assumptions

- Users rely on schema-provided nullability rules to determine whether null values are acceptable.
