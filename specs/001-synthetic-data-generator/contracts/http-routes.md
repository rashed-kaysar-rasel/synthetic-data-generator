# HTTP Routes & Contracts

**Date**: 2025-12-23

This document outlines the primary web routes for the application. As this is an Inertia.js application, there is no separate API. These routes handle page loads and form submissions.

## Page Routes

### 1. `GET /`

-   **Controller**: `SchemaController@index`
-   **Description**: Displays the main page where users can upload their SQL DDL file.
-   **Inertia Component**: `Generator/Index.jsx`

### 2. `GET /configure`

-   **Controller**: `SchemaController@show`
-   **Description**: Displays the schema visualization and configuration UI. This page is shown after a schema has been successfully parsed and stored in the session.
-   **Inertia Component**: `Generator/Configure.jsx`
-   **Data Passed**: The parsed schema structure (tables, columns, relationships).

## Action Routes

### 1. `POST /schema`

-   **Controller**: `SchemaController@store`
-   **Request Body**: `multipart/form-data` with a `ddl_file` field.
-   **Description**: Handles the upload of the SQL DDL file. It parses the file, stores the schema in the user's session, and redirects to the `/configure` route.
-   **Success Response**: Redirect to `/configure`.
-   **Error Response**: Redirect back to `/` with a validation error message.

### 2. `POST /generate`

-   **Controller**: `DataGenerationController@store`
-   **Request Body**: A JSON object matching the `GenerationConfig` data model.
    ```json
    {
      "seed": 1234,
      "tables": {
        "users": {
          "rowCount": 100,
          "columns": {
            "name": { "provider": "faker.name" },
            "email": { "provider": "faker.email" }
          }
        }
      },
      "format": "sql"
    }
    ```
-   **Description**: Kicks off the data generation process. It validates the configuration and dispatches a `GenerateDataJob` to the queue. It returns an immediate response to the user.
-   **Success Response**: A JSON response indicating the job has been queued. `{"status": "pending", "job_id": "..."}`.
-   **Error Response**: A JSON response with validation errors.

### 3. `GET /jobs/{job_id}`

-   **Controller**: `DataGenerationController@show`
-   **Description**: Polled by the frontend to check the status of a generation job.
-   **Success Response**: A JSON response with the job status.
    -   Pending: `{"status": "pending"}`
    -   Completed: `{"status": "completed", "download_url": "/download/..."}`
    -   Failed: `{"status": "failed", "error": "..."}`

### 4. `GET /download/{file_name}`

-   **Controller**: `DataGenerationController@download`
-   **Description**: Allows the user to download the generated file once the job is complete. This route should be protected to ensure only the user who initiated the job can download the file.
-   **Success Response**: A file download response (`Content-Type: application/zip` or `text/sql`).
