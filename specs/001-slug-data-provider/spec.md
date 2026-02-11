# Feature Specification: Slug Data Provider Option

**Feature Branch**: `001-slug-data-provider`  
**Created**: 2026-02-11  
**Status**: Draft  
**Input**: User description: "Their are lots of db colum called slug. For example course_slug, slug , slugs usually generate from the titles or a string. In data provider I want option for slug type data. If select slug, user need to select another column from where the slug will be generate"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Choose Slug Type with Source Column (Priority: P1)

A user configuring data providers for a table chooses a "slug" data type for a target column and selects another column as the source for slug generation.

**Why this priority**: This is the core value—users need a reliable way to populate slug columns based on existing text.

**Independent Test**: Can be fully tested by selecting "slug" for a column, choosing a source column, and verifying generated output uses the source text.

**Acceptance Scenarios**:

1. **Given** a column configuration screen, **When** the user selects the "slug" data type, **Then** the system requires a source column selection before saving.
2. **Given** a selected source column, **When** the user saves the configuration, **Then** the configuration is stored with the chosen source.

---

### User Story 2 - Slug Values Generated from Source (Priority: P2)

A user generates data and expects the slug values to be derived from the selected source column’s text.

**Why this priority**: The generated data must reflect the intended relationship between source text and slug columns.

**Independent Test**: Can be tested by generating a dataset where a known source value produces a predictable slug.

**Acceptance Scenarios**:

1. **Given** a row with source value "My First Course", **When** data is generated, **Then** the slug value becomes "my-first-course".

---

### User Story 3 - Handle Missing Source Values (Priority: P3)

A user wants predictable behavior when the source column value is empty or missing.

**Why this priority**: Prevents confusing or invalid data in slug columns.

**Independent Test**: Can be tested by generating rows with empty source values and confirming slug output is empty.

**Acceptance Scenarios**:

1. **Given** a row with an empty or null source value, **When** data is generated, **Then** the slug value is empty.

---

### Edge Cases

- What happens when the user selects "slug" but does not choose a source column?
- How does the system handle source values with punctuation, multiple spaces, or non-alphanumeric characters?
- What happens when the source value is extremely long?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST provide "slug" as a selectable data type for column data providers.
- **FR-002**: When "slug" is selected, system MUST require the user to choose a source column before saving the configuration.
- **FR-003**: Source column choices MUST be limited to columns containing text values.
- **FR-004**: System MUST generate slug values by transforming the source text to lowercase, trimming leading/trailing whitespace, replacing internal whitespace with hyphens, removing non-alphanumeric characters (except hyphens), and collapsing repeated hyphens.
- **FR-005**: If the source value is empty or null, the generated slug MUST be empty.
- **FR-006**: If a previously selected source column becomes unavailable, system MUST block saving until a valid source column is selected.
- **FR-007**: Generated data MUST reflect the current source column selection at the time of generation.

### Key Entities *(include if feature involves data)*

- **Column Configuration**: User-defined data provider settings for a target column, including data type and source column (if applicable).
- **Source Column**: The text-based column whose values are used to derive slug output.
- **Slug Output**: Generated string value derived from the source column text.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A user can configure a slug column (including selecting a source column) in under 1 minute without external help.
- **SC-002**: For rows with non-empty source values, 100% of generated slug outputs match the specified transformation rules.
- **SC-003**: For rows with empty source values, 100% of generated slug outputs are empty.
- **SC-004**: At least 90% of users report that slug configuration is "clear" or "very clear" in post-task feedback.

## Assumptions

- Slug generation does not enforce uniqueness; duplicates are allowed unless the user manages this elsewhere.
- Source columns are limited to text-like fields only.

## Out of Scope

- Configurable slug formats (custom separators, casing options, or locale-specific transliteration).
- Automatic uniqueness enforcement or collision resolution.
