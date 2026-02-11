# Quickstart: Slug Data Provider Option

## Goal
Configure a slug column that derives values from a source text column.

## Prerequisites
- App running locally.
- A SQL schema with at least two text-like columns in the same table (e.g., `title` and `slug`).

## Steps
1. Upload the SQL DDL schema in the generator UI.
2. On the configure screen, locate the target slug column.
3. Choose `text.slug` as the data provider for the slug column.
4. Select a source column (e.g., `title`) in the slug source selector.
5. Click `Generate Data`.
6. Verify output rows show slug values derived from the selected source column (e.g., `My First Course` -> `my-first-course`).

## Expected Results
- The UI prevents saving or generating if the slug provider is selected without a source column.
- Generated slug values follow the defined transformation rules.
