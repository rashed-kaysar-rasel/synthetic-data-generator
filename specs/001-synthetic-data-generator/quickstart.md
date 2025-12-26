# Quickstart: Synthetic Data Generator

**Date**: 2025-12-26

This guide provides instructions for setting up and running the project locally.

## Prerequisites

-   PHP 8.3+
-   Composer
-   Node.js 22.x+
-   NPM or Yarn
-   A MySQL database

## Local Setup

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/your-repo/synthetic-data-generator.git
    cd synthetic-data-generator
    ```

2.  **Install PHP dependencies**:
    ```bash
    cd backend
    composer install
    cd ..
    ```

3.  **Install JavaScript dependencies**:
    ```bash
    cd backend
    npm install
    # or
    yarn install
    cd ..
    ```

4.  **Set up environment file**:
    -   Copy the `.env.example` file to `.env`:
        ```bash
        cp backend/.env.example backend/.env
        ```
    -   Generate an application key:
        ```bash
        php artisan key:generate --path=backend/
        ```

5.  **Configure your `.env` file**:
    -   Update the `DB_*` variables in `backend/.env` to point to your local MySQL database.
    -   Configure a queue driver. For local development, `sync` or `database` is recommended. To use the `database` driver, run:
        ```bash
        php artisan queue:table --path=backend/
        php artisan migrate --path=backend/
        ```

6.  **Run database migrations**:
    ```bash
    php artisan migrate --path=backend/
    ```

7.  **Build frontend assets**:
    -   For development (with hot-reloading):
        ```bash
        npm run dev --prefix backend/
        ```
    -   For production:
        ```bash
        npm run build --prefix backend/
        ```

## Running the Application

1.  **Start the development server**:
    ```bash
    php artisan serve --working-dir=backend/
    ```

2.  **Start the queue worker** (if not using the `sync` driver):
    ```bash
    php artisan queue:work --working-dir=backend/
    ```

3.  **Access the application**:
    -   Open your browser and navigate to `http://127.0.0.1:8000`.

## Workflow

1.  **Upload DDL**: On the main page, upload a SQL DDL file to define your schema.
2.  **Configure Generation**: You will be redirected to the configuration page. Here you can set the number of rows for each table and map columns to data providers.
3.  **Generate Data**: Click the "Generate" button. The request will be sent to the queue for processing.
4.  **Download**: Once the job is complete, a download link will appear. Click it to download your synthetic data file.
