# Data Model: Saved Generation Configurations

## Entities

### SavedConfiguration
Represents a stored generator configuration for a user.

Fields:
- `userId` (reference)
- `name` (string)
- `schemaSignature` (string)
- `payload` (object)
- `createdAt` (datetime)
- `updatedAt` (datetime)

Validation rules:
- `name` is required and user-unique.
- `payload` must include table row counts and column providers.
- `schemaSignature` must match the current schema to import.

### SchemaSignature
Derived identifier for a schema.

Rules:
- Deterministically computed from table names, column names, and data types.

## Relationships
- `SavedConfiguration.userId` references `User`.
