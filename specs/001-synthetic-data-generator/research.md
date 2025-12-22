# Research & Decisions: Web-Based Synthetic Data Generator

**Date**: 2025-12-23
**Status**: Completed

## 1. SQL Parser

-   **Decision**: Use the `php-sql-parser` library.
-   **Rationale**: This is a popular, well-maintained, and robust library for parsing SQL in PHP. It can handle various SQL dialects and provides a detailed Abstract Syntax Tree (AST), which will be essential for extracting table names, columns, and foreign key constraints from the user's DDL file.
-   **Alternatives considered**:
    -   Writing a custom parser: This would be too time-consuming and error-prone.
    -   Other libraries: `php-sql-parser` is the most mature and widely used option in the PHP ecosystem.

## 2. UI Component Library

-   **Decision**: Use `shadcn/ui`.
-   **Rationale**: The user explicitly requested `shadcn/ui`. It's a modern, accessible, and highly customizable component library for React. It provides a great foundation for building the configuration UI quickly while maintaining a professional look and feel. The components are unstyled by default, giving us full control over the application's appearance.
-   **Alternatives considered**:
    -   Material-UI, Ant Design: These are more opinionated component libraries. `shadcn/ui` offers more flexibility.
    -   Building components from scratch: This would be too time-consuming for the initial version.

## 3. Background Job Processing

-   **Decision**: Use Laravel Queues.
-   **Rationale**: The user explicitly requested Laravel Queues. This is the native solution for handling background jobs in Laravel. It's essential for processing large data generation requests without blocking the UI or timing out. It integrates seamlessly with the rest of the framework and supports various queue drivers (database, Redis, etc.).
-   **Alternatives considered**:
    -   Synchronous processing: Not feasible for large datasets as it would lead to HTTP timeouts and a poor user experience.
    -   Custom queue implementation: Unnecessary, as Laravel provides a robust and well-tested solution out of the box.
