# Feature Specification: Web-Based Synthetic Data Generator

**Feature Branch**: `001-synthetic-data-generator`
**Created**: 2025-12-23
**Status**: Draft
**Input**: User description: "I want to build a web-based Synthetic Data Generator that enables developers to upload a SQL DDL file and generate relationally consistent, PII-free synthetic datasets in SQL or CSV formats. The system must feature a robust SQL Parser to extract table schemas and Foreign Key relationships, a Topological Sort algorithm to determine the correct data insertion order, and a Configuration UI where users can map columns to specific data providers and define row densities. Key technical requirements include the use of PHP Generators for memory-efficient streaming of large datasets, a deterministic seeding mechanism for reproducible environments, and a 'Schema-First' architecture that maintains 100% relational integrity without requiring modifications to the user's original database structure. Conclude the workflow by providing a downloadable, CSV or SQL batch-insert dump file, ensuring th"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Schema Upload and Visualization (Priority: P1)

As a developer, I want to upload a SQL DDL file so that the system can parse it and display a visual representation of the table schemas and their relationships.

**Why this priority**: This is the foundational step of the entire process. Without understanding the database schema, no data generation is possible.

**Independent Test**: A user can upload a valid DDL file and see a list of tables, their columns, and foreign key connections rendered correctly on the screen.

**Acceptance Scenarios**:

1.  **Given** a user is on the homepage, **When** they upload a valid SQL DDL file, **Then** the system should display a list of parsed tables and columns.
2.  **Given** a schema has been parsed, **When** there are foreign key relationships, **Then** the system should visually indicate these relationships between the tables.

---

### User Story 2 - Generation Configuration (Priority: P2)

As a developer, I want to configure the data generation by mapping specific data providers to table columns and defining the number of rows for each table.

**Why this priority**: This step provides the necessary control to create meaningful and appropriately sized synthetic data.

**Independent Test**: A user can select a table from the visualized schema, choose a data provider (e.g., "Full Name", "Email Address") for a column, and set the desired row count for that table. The configuration is saved.

**Acceptance Scenarios**:

1.  **Given** a schema is visualized, **When** a user selects a column, **Then** they are presented with a list of available data providers to choose from.
2.  **Given** a schema is visualized, **When** a user selects a table, **Then** they can input a number to specify how many rows to generate.

---

### User Story 3 - Data Generation and Download (Priority: P3)

As a developer, I want to trigger the data generation process and download the resulting synthetic dataset as a single SQL or CSV file.

**Why this priority**: This is the final deliverable and the ultimate goal of the user.

**Independent Test**: A user can click a "Generate" button, and after processing, they receive a link to download a file in their chosen format (SQL or CSV) that contains the generated data.

**Acceptance Scenarios**:

1.  **Given** the data generation is configured, **When** the user clicks "Generate Data", **Then** the system processes the request and provides a download link.
2.  **Given** a download link is available, **When** the user chooses the "SQL" format, **Then** the downloaded file should contain valid SQL `INSERT` statements.
3.  **Given** a download link is available, **When** the user chooses the "CSV" format, **Then** the downloaded files should be in a valid CSV format (e.g., zipped folder of CSVs).

---

### Edge Cases

-   **Invalid DDL**: What happens when the uploaded DDL file has syntax errors? The system should display a user-friendly, detailed error message in the UI, informing the user about the parsing issue and offering a retry option.
-   **Circular Dependencies**: How does the system handle schemas with circular foreign key relationships? The topological sort must be able to handle this, potentially by allowing one of the foreign keys to be nullable.
-   **Large Datasets**: How does the system handle a request to generate millions of rows? The process should be handled asynchronously. If the background job fails, the UI should display a detailed error message with a retry button.
-   **Unsupported SQL Dialect**: What happens if the DDL uses features specific to an unsupported SQL dialect? The parser should gracefully handle recognized features and report unsupported ones.

## Requirements *(mandatory)*

### Functional Requirements

-   **FR-001**: The system MUST allow users to upload a SQL DDL file.
-   **FR-002**: The system MUST parse the DDL to extract table names, column names, data types, and foreign key relationships.
-   **FR-003**: The system MUST use a topological sort algorithm to determine the correct data insertion order to maintain relational integrity.
-   **FR-004**: The system MUST provide a UI for users to map data providers (e.g., fake name, address, phone number) to specific table columns.
-   **FR-005**: The system MUST allow users to specify the number of rows to generate for each table.
-   **FR-006**: The system MUST generate data that is relationally consistent and PII-free.
-   **FR-007**: The system MUST support generating output in both CSV (as a single ZIP file containing one CSV per table) and SQL (batch inserts) formats.
-   **FR-008**: The system MUST provide the generated data as a downloadable file.
-   **FR-009**: The data generation process MUST be deterministic based on a configurable seed, allowing for reproducible datasets.
-   **FR-010**: The system MUST handle large datasets efficiently without consuming excessive memory.

### Key Entities *(include if feature involves data)*

-   **Schema**: Represents the entire database structure, containing a collection of tables and their relationships.
-   **Table**: Represents a single database table, containing a name and a list of columns.
-   **Column**: Represents a table column, with attributes like name, data type, and whether it's a primary or foreign key.
-   **Relationship**: Represents a foreign key constraint between two tables.
-   **DataProvider**: A function or class that can generate a specific type of fake data (e.g., names, emails, cities).
-   **GenerationConfig**: A user-defined configuration that maps columns to DataProviders and specifies row counts for tables.

## Assumptions

-   The initial version will focus on supporting the **MySQL** SQL dialect. Support for other dialects can be added later.
-   Users are developers or have a technical background with a basic understanding of SQL and database schemas.
-   The system will provide a pre-defined set of common data providers. A mechanism for users to create custom providers is out of scope for the initial version.

## Clarifications

### Session 2025-12-23

-   **Q**: Which SQL dialect should be prioritized for the initial implementation? → **A**: MySQL
-   **Q**: How should the UI inform the user if a background data generation job fails? → **A**: Display a detailed error message in the UI with error details and a "Retry" button
-   **Q**: For the CSV download option with multiple tables, what should the output format be? → **A**: A single ZIP file containing one CSV per table

## Success Criteria *(mandatory)*

### Measurable Outcomes

-   **SC-001**: 95% of valid SQL-92 DDL files under 1MB can be successfully parsed in under 5 seconds.
-   **SC-002**: A user can configure a 10-table schema (mapping providers and row counts) in under 10 minutes.
-   **SC-003**: The system can generate a 1-million-row dataset across 10 tables with relational integrity in under 5 minutes.
-   **SC-004**: The generated data MUST have 100% relational integrity, meaning all foreign key constraints are satisfied.
-   **SC-005**: The peak memory usage during the generation of a 1-million-row dataset must be less than 512MB.