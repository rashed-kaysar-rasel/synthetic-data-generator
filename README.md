# Synthetic Data Generator

## Project Overview

The Synthetic Data Generator is a web-based application designed to help developers and testers create realistic, structured synthetic data for their applications. Users can upload a SQL DDL (Data Definition Language) file to define their database schema, configure data generation rules for each table and column, and then generate and download the synthetic data in various formats (SQL INSERT statements or CSV).

This tool aims to simplify the process of populating development and testing databases with relevant, yet fake, data, ensuring data privacy and reducing reliance on sensitive production data.

## Features

-   **SQL DDL Parsing**: Upload a SQL DDL file to automatically parse and visualize your database schema.
-   **Schema Visualization**: View tables, columns, primary keys, and foreign key relationships.
-   **Configurable Data Generation**: Define data generation rules for each column using a variety of fake data providers.
-   **Row Count Control**: Specify the number of rows to generate for each table.
-   **Topological Sorting**: Ensures data is generated in the correct order, respecting foreign key constraints.
-   **Background Processing**: Data generation runs as a background job, preventing timeouts for large datasets.
-   **Multiple Output Formats**: Download generated data as SQL INSERT statements or CSV files.
-   **Job Status Monitoring**: Track the progress of your data generation jobs with real-time status updates.
-   **Retry Mechanism**: Easily retry failed data generation jobs.

## Technical Stack

-   **Backend**: PHP 8.3+, Laravel 12, Laravel Queues
-   **Frontend**: Node.js 22.x+, Blade, Tailwind CSS, vanilla JavaScript
-   **Database Parsing**: `php-sql-parser`
-   **Fake Data Generation**: Faker library (integrated into PHP backend)
-   **Database**: MySQL (for application's own data, not generated data)
-   **Testing**: PHPUnit (PHP)
-   **Deployment Target**: Web (Modern Browsers)

## Prerequisites

To run this project locally, you will need:

-   **PHP**: Version 8.3 or higher.
-   **Composer**: PHP dependency manager.
-   **Node.js**: Version 22.x or higher.
-   **NPM or Yarn**: JavaScript package manager.
-   **MySQL Database**: A local MySQL server for the application's own database.

## Local Setup Instructions

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/your-repo/synthetic-data-generator.git
    cd synthetic-data-generator
    ```

2.  **Install PHP dependencies**:
    Navigate into the `backend` directory and install Composer dependencies.
    ```bash
    cd backend
    composer install
    ```
    Then return to the project root.
    ```bash
    cd ..
    ```

3.  **Install JavaScript dependencies**:
    Navigate into the `backend` directory and install NPM dependencies.
    ```bash
    cd backend
    npm install
    # or if you prefer Yarn
    # yarn install
    ```
    Then return to the project root.
    ```bash
    cd ..
    ```

4.  **Set up environment file**:
    -   Copy the `.env.example` file from the `backend` directory to `.env` in the same directory:
        ```bash
        cp backend/.env.example backend/.env
        ```
    -   Generate an application key:
        ```bash
        php artisan key:generate --ansi --env=.env --path=backend/
        ```

5.  **Configure your `.env` file**:
    -   Open `backend/.env` and update the `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` variables to point to your local MySQL database.
    -   Configure a queue driver. For local development, `sync` or `database` is recommended. To use the `database` driver, ensure your `.env` has `QUEUE_CONNECTION=database`, then run:
        ```bash
        php artisan queue:table --path=backend/
        php artisan migrate --path=backend/
        ```
        (Note: Replace `backend/` with the correct path to your Laravel application if it's not directly in `backend/` from your working directory when running `php artisan` commands.)

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

2.  **Start the queue worker** (if not using the `sync` queue driver and background jobs are expected):
    ```bash
    php artisan queue:work --working-dir=backend/
    ```

3.  **Access the application**:
    -   Open your web browser and navigate to `http://127.0.0.1:8000` (or whatever address `php artisan serve` indicates).

## Usage Workflow

1.  **Upload DDL**: On the main page, upload a SQL DDL file containing your database schema.
2.  **Configure Generation**: You will be redirected to the configuration page. Here, you can:
    -   Set the number of rows to generate for each table.
    -   Map columns to various fake data providers (e.g., `faker.name`, `faker.email`).
    -   Choose the output format (SQL INSERTs or CSV).
    -   Optionally provide a random seed for reproducible data generation.
3.  **Generate Data**: Click the "Generate Data" button. The request will be sent to a background queue for processing.
4.  **Monitor Status**: The UI will display the job's current status (pending, completed, failed).
5.  **Download**: Once the job is complete, a download link will appear. Click it to download your generated synthetic data file.

## Contribution

We welcome contributions! Please feel free to open issues or submit pull requests. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is open-sourced under the [MIT License](LICENSE).
