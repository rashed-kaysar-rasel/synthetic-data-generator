# Data Model

## Entity: Schema Column

- **Attributes**: name, dataType, nullable, isPrimaryKey, isForeignKey, isUnique, autoIncrement, defaultValue
- **Notes**: Data type drives value generation for numeric fields such as double.

## Entity: Generated Row

- **Attributes**: tableName, columnValues
- **Notes**: Each generated row must include valid numeric values for double columns unless nullability allows otherwise.
