# Research & Decisions: Web-Based Synthetic Data Generator

**Date**: 2025-12-23
**Status**: Completed

## 1. SQL Parser

-   **Decision**: Use the `php-sql-parser` library.
-   **Rationale**: This is a popular, well-maintained, and robust library for parsing SQL in PHP. It can handle various SQL dialects and provides a detailed Abstract Syntax Tree (AST), which will be essential for extracting table names, columns, and foreign key constraints from the user's DDL file.
-   **Alternatives considered**:
    -   Writing a custom parser: This would be too time-consuming and error-prone.
    -   Other libraries: `php-sql-parser` is the most mature and widely used option in the PHP ecosystem.

## 2. Frontend UI Approach

-   **Decision**: Use Blade templates with Tailwind CSS and vanilla JavaScript.
-   **Rationale**: This aligns with the project's constitution and keeps the UI simple,
    server-rendered, and maintainable without SPA frameworks.
-   **Alternatives considered**:
    -   React + Inertia.js: Rejected to meet the Blade-first constraint.
    -   Heavy component libraries: Rejected to keep the UI lightweight and framework-agnostic.

## 3. Background Job Processing

-   **Decision**: Use Laravel Queues.
-   **Rationale**: The user explicitly requested Laravel Queues. This is the native solution for handling background jobs in Laravel. It's essential for processing large data generation requests without blocking the UI or timing out. It integrates seamlessly with the rest of the framework and supports various queue drivers (database, Redis, etc.).
-   **Alternatives considered**:
    -   Synchronous processing: Not feasible for large datasets as it would lead to HTTP timeouts and a poor user experience.
    -   Custom queue implementation: Unnecessary, as Laravel provides a robust and well-tested solution out of the box.
