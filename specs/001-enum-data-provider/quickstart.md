# Quickstart: Enum Data Provider

## Goal
Configure a column to generate values from a user-defined enum list.

## Prerequisites
- App running locally.
- A SQL schema with at least one text-like column you want to treat as an enum (e.g., `status`).

## Steps
1. Upload the SQL DDL schema in the generator UI.
2. On the configure screen, locate the target column (e.g., `status`).
3. Choose the enum data provider (`text.enum`) for that column.
4. Enter allowed values in the enum values field (comma or newline separated).
5. Click `Generate Data`.
6. Verify output rows use only the provided enum values.

## Expected Results
- The UI prevents generating if the enum provider is selected without any values.
- Generated values for the enum column are always from the configured list.
