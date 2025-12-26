# Research & Decisions: SQL Parse Accuracy

**Date**: 2025-12-26
**Status**: Completed

## 1. SQL Dialect Scope

- **Decision**: Support MySQL/MariaDB dump syntax.
- **Rationale**: The reference dump (`backend/db_pltte.sql`) and existing parser
  are optimized for MySQL-style DDL, which covers the primary user needs.
- **Alternatives considered**:
  - Full multi-dialect support: Out of scope for this feature due to complexity.

## 2. Dump Parsing Coverage

- **Decision**: Parse `CREATE TABLE` plus `ALTER TABLE ... ADD CONSTRAINT` blocks,
  and ignore non-DDL statements (comments, SET, COMMIT).
- **Rationale**: Many dumps defer constraints and indexes to ALTER statements.
  Ignoring those causes incomplete relationships and FK violations.
- **Alternatives considered**:
  - Rely on CREATE TABLE only: Insufficient for phpMyAdmin and similar dumps.

**Observed dump patterns (phpMyAdmin/MariaDB)**:
- `ALTER TABLE ... ADD PRIMARY KEY`, `ADD UNIQUE KEY`, and `ADD KEY` blocks.
- `ALTER TABLE ... ADD CONSTRAINT ... FOREIGN KEY` blocks.
- `ALTER TABLE ... MODIFY ... AUTO_INCREMENT` statements.

## 3. Constraint-Aware Generation

- **Decision**: Enforce primary keys, unique constraints, and foreign keys during
  generation, and fail fast with a clear error when constraints are unsatisfiable.
- **Rationale**: Import-ready output is the core value of the tool.
- **Alternatives considered**:
  - Generate and hope import succeeds: Leads to silent failures and poor UX.

## 4. Identifier Normalization

- **Decision**: Normalize quoted and schema-qualified identifiers before mapping
  constraints and columns.
- **Rationale**: Dumps frequently use backticks and schema prefixes which can
  break constraint matching if not normalized.
- **Alternatives considered**:
  - Use raw identifiers: Causes mismatches across CREATE/ALTER statements.
