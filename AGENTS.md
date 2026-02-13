# synthetic-data-generator Development Guidelines

Auto-generated from all feature plans. Last updated: 2025-12-26

## Active Technologies
- PHP 8.3+, Node.js 22.x + Laravel 12, Tailwind CSS, Vite, greenlion/php-sql-parser, Laravel Queues (001-direct-db-connection)
- External MySQL/PostgreSQL (target databases); no new persistent storage for credentials (001-direct-db-connection)
- PHP 8.3, Node.js 22, Laravel 12, vanilla JavaScript + Laravel, Tailwind CSS, Vite (001-fix-insert-button-text)
- External MySQL/PostgreSQL targets; generated SQL files on server disk (001-fix-insert-button-text)
- PHP 8.3, Node.js 22, Laravel 12 + Laravel framework, Faker (via Laravel), Tailwind CSS, Vite (001-fix-double-null)
- External MySQL/PostgreSQL targets; generated SQL/CSV files on server disk (001-fix-double-null)
- PHP 8.3, Node.js 22, Laravel 12 + Laravel 12, Faker (Laravel), Tailwind CSS, Vite (001-enum-data-provider)
- Generated SQL/CSV files on server disk; optional direct insert to external MySQL/PostgreSQL; no persistent credential storage (001-enum-data-provider)
- PHP 8.3, Node.js 22, Laravel 12 + Laravel 12, Faker (Laravel), greenlion/php-sql-parser, Tailwind CSS, Vite (001-slug-data-provider)
- Generated SQL/CSV files on server disk; optional direct insert to external MySQL/PostgreSQL; no persistent credential storage (001-slug-data-provider)
- PHP 8.3+, Node.js 22.x + Laravel 12, Tailwind CSS, Vite, php-sql-parser, Laravel Queues (001-sql-parse-accuracy)

## Project Structure

```text
backend/
frontend/
tests/
```

## Commands

# Add commands for PHP 8.3+, Node.js 22.x

## Code Style

PHP 8.3+, Node.js 22.x: Follow standard conventions

## Recent Changes
- 001-saved-config: Added PHP 8.3, Node.js 22, Laravel 12 + Laravel 12, Faker (Laravel), Tailwind CSS, Vite
- 001-auth-module: Added PHP 8.3, Node.js 22, Laravel 12 + Laravel 12, Faker (Laravel), Tailwind CSS, Vite

- 001-enum-data-provider: Added PHP 8.3, Node.js 22, Laravel 12 + Laravel 12, Faker (Laravel), Tailwind CSS, Vite


<!-- MANUAL ADDITIONS START -->
<!-- MANUAL ADDITIONS END -->
