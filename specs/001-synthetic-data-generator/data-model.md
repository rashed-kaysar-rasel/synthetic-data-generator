# Data Models: Synthetic Data Generator

**Date**: 2025-12-23

This document describes the main data structures used within the application. These are not database models but rather in-memory representations of the user's schema and configuration.

## 1. Schema

Represents the entire database structure parsed from the user's DDL file.

-   **`tables`**: `Collection<Table>` - A collection of all tables in the schema.
-   **`relationships`**: `Collection<Relationship>` - A collection of all foreign key relationships.

## 2. Table

Represents a single database table.

-   **`name`**: `string` - The name of the table (e.g., `users`).
-   **`columns`**: `Collection<Column>` - A collection of columns in the table.

## 3. Column

Represents a single column in a table.

-   **`name`**: `string` - The name of the column (e.g., `first_name`).
-   **`dataType`**: `string` - The SQL data type of the column (e.g., `VARCHAR(255)`, `INT`).
-   **`isPrimaryKey`**: `boolean` - Whether the column is a primary key.
-   **`isForeignKey`**: `boolean` - Whether the column is a foreign key.

## 4. Relationship

Represents a foreign key relationship between two tables.

-   **`from_table`**: `string` - The name of the table with the foreign key.
-   **`from_column`**: `string` - The name of the column with the foreign key.
-   **`to_table`**: `string` - The name of the table being referenced.
-   **`to_column`**: `string` - The name of the column being referenced.

## 5. GenerationConfig

Represents the user's configuration for data generation. This is the primary data structure that will be passed to the backend to initiate a generation job.

-   **`seed`**: `int` - The seed for the random number generator to ensure deterministic output.
-   **`tables`**: `array` - An associative array where keys are table names.
    -   **`[tableName]`**: `object`
        -   **`rowCount`**: `int` - The number of rows to generate for this table.
        -   **`columns`**: `array` - An associative array where keys are column names.
            -   **`[columnName]`**: `object`
                -   **`provider`**: `string` - The name of the data provider to use (e.g., `faker.name`, `faker.email`).
                -   **`options`**: `array` (optional) - Any options for the provider.

## 6. DataProvider

Represents a provider of fake data. This will be represented as a list of available providers on the frontend, likely sourced from a backend configuration file.

-   **`name`**: `string` - A user-friendly name (e.g., "Full Name", "E-mail Address").
-   **`providerKey`**: `string` - The key to be used in the `GenerationConfig` (e.g., `faker.name`).
-   **`description`**: `string` - A brief description of the data it generates.
