# Data Model: Enum Data Provider

## Entities

### ColumnConfiguration
Represents user-selected generation settings for a table column.

Fields:
- `provider` (string | null): Data provider key (e.g., `text.enum`).
- `enumValues` (array<string> | null): Allowed values when `provider` is `text.enum`.

Validation rules:
- `enumValues` is required when `provider` is `text.enum`.
- `enumValues` must contain at least one non-empty value after trimming.

### TableConfiguration
Represents generation configuration for a table.

Fields:
- `rowCount` (integer): Number of rows to generate.
- `columns` (map<string, ColumnConfiguration>): Per-column generation settings.

### SchemaColumn
Represents a parsed column from the user-provided SQL DDL or database connection.

Fields (selected):
- `name` (string)
- `dataType` (string)
- `nullable` (boolean)
- `isPrimaryKey` (boolean)
- `isForeignKey` (boolean)
- `isUnique` (boolean)

### EnumOutput
Derived value created at generation time.

Rules:
- Values are chosen from the configured `enumValues` list.
- Empty list is invalid and blocked by validation.

## Relationships
- `TableConfiguration.columns` references `SchemaColumn.name`.
- `ColumnConfiguration.enumValues` applies to a single column in the same table.
