# Data Model: Slug Data Provider Option

## Entities

### ColumnConfiguration
Represents user-selected generation settings for a table column.

Fields:
- `provider` (string | null): Data provider key (e.g., `text.slug`).
- `slugSourceColumn` (string | null): Source column name used when `provider` is `text.slug`.

Validation rules:
- `slugSourceColumn` is required when `provider` is `text.slug`.
- `slugSourceColumn` must refer to a text-like column in the same table.

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

### SlugOutput
Derived value created at generation time.

Rules:
- Lowercase
- Trim whitespace
- Replace internal whitespace with `-`
- Remove non-alphanumeric characters except `-`
- Collapse repeated `-`
- Empty when source is null/empty

## Relationships
- `TableConfiguration.columns` references `SchemaColumn.name`.
- `ColumnConfiguration.slugSourceColumn` references a `SchemaColumn.name` in the same table.
