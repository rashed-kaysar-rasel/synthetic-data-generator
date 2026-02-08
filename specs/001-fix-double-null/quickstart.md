# Quickstart

## Goal
Verify that double columns generate numeric values.

## Steps

1. Provide a schema with at least one `double` column and mark it non-nullable.
2. Run a data generation job using existing UI/API.
3. Inspect the generated output (SQL or CSV) and confirm the double column contains numeric values for every row.
4. Confirm there are no NULL values for the non-nullable double column.
5. Repeat with a nullable double column and confirm nulls only appear if nullability allows it.
