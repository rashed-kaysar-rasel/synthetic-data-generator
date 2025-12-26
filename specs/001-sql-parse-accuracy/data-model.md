# Data Model: SQL Parse Accuracy

**Date**: 2025-12-26

## Entities

### SQLSchema

Represents the parsed structure of the SQL dump.

- **tables**: list of Table
- **relationships**: list of Constraint (foreign keys)
- **indexes**: list of Index

### Table

Represents a database table definition.

- **name**: string
- **columns**: list of Column
- **constraints**: list of Constraint
- **indexes**: list of Index

### Column

Represents a table column.

- **name**: string
- **dataType**: string
- **nullable**: boolean
- **defaultValue**: string | null
- **autoIncrement**: boolean
- **isPrimaryKey**: boolean
- **isForeignKey**: boolean
- **isUnique**: boolean

### Constraint

Represents a constraint applied to one or more columns.

- **type**: enum (primary_key, foreign_key, unique)
- **columns**: list of string
- **referenceTable**: string | null
- **referenceColumns**: list of string | null

### Index

Represents an index definition.

- **name**: string
- **columns**: list of string
- **unique**: boolean

### GenerationConfig

User-provided configuration for data generation.

- **format**: enum (sql, csv)
- **seed**: integer | null
- **tables**: map of tableName -> TableConfig

### TableConfig

Per-table generation settings.

- **rowCount**: integer
- **columns**: map of columnName -> ColumnConfig

### ColumnConfig

Per-column generation settings.

- **provider**: string | null

### JobStatus

Represents background job state for generation.

- **status**: enum (pending, completed, failed)
- **downloadUrl**: string | null
- **error**: string | null
