# HTTP Routes & Contracts

**Date**: 2025-12-26

This document outlines the primary web routes for the application. The UI is
rendered with Blade and enhanced with JavaScript.

## Page Routes

### 1. `GET /`

- **Controller**: `SchemaController@index`
- **Description**: Displays the main page where users upload a SQL dump file.
- **View**: `resources/views/generator/index.blade.php`

### 2. `GET /configure`

- **Controller**: `SchemaController@show`
- **Description**: Displays the schema and configuration UI after parsing.
- **View**: `resources/views/generator/configure.blade.php`
- **Data Passed**: Parsed schema (tables, columns, constraints).

## Action Routes

### 1. `POST /schema`

- **Controller**: `SchemaController@store`
- **Request Body**: `multipart/form-data` with `ddl_file`.
- **Description**: Parses the SQL dump, extracts tables/constraints, stores schema
  in session, and redirects to `/configure`.
- **Success Response**: Redirect to `/configure`.
- **Error Response**: Redirect back to `/` with a validation or parsing error.

### 2. `POST /generate`

- **Controller**: `DataGenerationController@store`
- **Request Body**: JSON `GenerationConfig`.
    ```json
    {
      "seed": 1234,
      "tables": {
        "pltte_questions": {
          "rowCount": 10,
          "columns": {
            "question": { "provider": "text.sentence" }
          }
        }
      },
      "format": "sql"
    }
    ```
- **Description**: Validates the configuration against constraints and starts
  generation. Returns status and job id or download URL (sync).
- **Success Response**:
  - Async: `{"status": "pending", "job_id": "..."}`
  - Sync: `{"status": "completed", "download_url": "/download/..."}`
- **Error Response**:
  - `422` with constraint validation errors.

### 3. `GET /jobs/{job_id}`

- **Controller**: `DataGenerationController@show`
- **Description**: Polled by the frontend to check generation status.
- **Success Response**:
  - Pending: `{"status": "pending"}`
  - Completed: `{"status": "completed", "download_url": "/download/..."}`
  - Failed: `{"status": "failed", "error": "..."}`

### 4. `GET /download/{file_name}`

- **Controller**: `DataGenerationController@download`
- **Description**: Downloads the generated file.
