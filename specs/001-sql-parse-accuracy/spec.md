# Feature Specification: SQL Parse Accuracy

**Feature Branch**: `001-sql-parse-accuracy`  
**Created**: 2025-12-26  
**Status**: Draft  
**Input**: User description: "this fixes are , data should be generated perfactly basis on the all the indexes, all the foreign key, constrain should be equerately detected and any kind of sql file should be perse and generate data acurately. for referance plase chec the backend\db_pltte.sql file"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Import-Ready Generation (Priority: P1)

As a user, I can upload a SQL dump with constraints and generate data that imports
without violating foreign keys or unique constraints.

**Why this priority**: This is the core value of the tool; data that cannot be
imported is unusable.

**Independent Test**: Use `backend/db_pltte.sql` and generate data for all tables;
import succeeds with no constraint errors.

**Acceptance Scenarios**:

1. **Given** a SQL file containing `CREATE TABLE` and `ALTER TABLE ... ADD CONSTRAINT`
   statements, **When** I generate SQL data, **Then** the output imports without FK
   violations.
2. **Given** unique constraints on columns, **When** I generate data, **Then** no
   duplicate values are produced for those unique sets.

---

### User Story 2 - Real-World Dump Parsing (Priority: P2)

As a user, I can upload SQL dumps produced by common export tools and still get a
complete schema map (tables, columns, keys, constraints).

**Why this priority**: Users commonly provide dumps with comments, SET statements,
and deferred constraints.

**Independent Test**: Upload a dump with comments, SET/COMMIT, and multiple CREATE/
ALTER statements and verify the schema is fully detected.

**Acceptance Scenarios**:

1. **Given** a dump file with comments and transaction statements, **When** it is
   parsed, **Then** all tables and constraints are detected and non-DDL statements
   are ignored.

---

### User Story 3 - Constraint-Driven Failure Feedback (Priority: P3)

As a user, I receive a clear error when my requested row counts cannot satisfy
required constraints (e.g., unique keys with too many rows).

**Why this priority**: It prevents silent generation of invalid data and provides
actionable guidance.

**Independent Test**: Request more rows than a unique constraint can allow and
confirm that generation fails with a clear explanation.

**Acceptance Scenarios**:

1. **Given** a unique constraint with a finite value set, **When** the requested row
   count exceeds what can be generated, **Then** the system reports an actionable
   error and does not produce invalid output.

---

### Edge Cases

- Foreign keys defined only in `ALTER TABLE` blocks.
- Composite primary keys and composite foreign keys.
- Schema-qualified or quoted identifiers (e.g., `db.table` or `\`table\``).
- Tables with zero rows while child tables request rows.
- Dumps that include comments, SET statements, and transactions.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST parse SQL dumps that include `CREATE TABLE` statements and
  detect table names, columns, and column data types.
- **FR-002**: System MUST detect primary keys, unique constraints, foreign keys,
  and indexes, including those defined in `ALTER TABLE` statements.
- **FR-003**: System MUST generate data in an order that respects foreign key
  dependencies between tables.
- **FR-004**: Generated foreign key values MUST reference existing parent rows or
  be explicitly null only when the constraint allows nulls.
- **FR-005**: Generated data MUST satisfy primary key and unique constraints.
- **FR-006**: If constraints cannot be satisfied with the requested row counts,
  the system MUST report an actionable error and avoid producing invalid data.
- **FR-007**: System MUST ignore non-DDL statements (comments, SET, COMMIT) without
  failing schema extraction.
- **FR-008**: System MUST correctly interpret quoted and schema-qualified identifiers.
- **FR-009**: System MUST honor NOT NULL constraints by generating non-null values.
- **FR-010**: System MUST support SQL dumps that follow MySQL/MariaDB syntax; other
  SQL dialects are out of scope for this feature.

**Dependencies and Assumptions**:
- Users provide a SQL dump containing schema definitions.
- Constraints must be explicitly present in the dump to be enforced.

### Key Entities *(include if feature involves data)*

- **SQL Schema**: The parsed structure of tables, columns, and constraints from the dump.
- **Table**: A schema-defined entity with columns and constraints.
- **Column**: A named field with data type and constraint metadata.
- **Constraint**: Primary key, foreign key, or unique rule tied to one or more columns.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Using `backend/db_pltte.sql`, generated SQL imports with zero FK or
  unique constraint errors.
- **SC-002**: 100% of tables and constraints in supported dumps are detected and
  surfaced in the schema configuration.
- **SC-003**: When a requested row count violates a unique constraint, the user
  receives an error within 10 seconds and no invalid output is produced.
