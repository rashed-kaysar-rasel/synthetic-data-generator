# Quickstart: SQL Parse Accuracy Validation

**Date**: 2025-12-26

Use this guide to validate SQL dump parsing and constraint-aware generation.

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 22.x+
- MySQL/MariaDB

## Local Setup

1. **Install dependencies**:
   ```bash
   cd backend
   composer install
   npm install
   cd ..
   ```

2. **Configure environment**:
   ```bash
   cp backend/.env.example backend/.env
   php artisan key:generate --path=backend/
   ```

3. **Run migrations**:
   ```bash
   php artisan migrate --path=backend/
   ```

4. **Run the app**:
   ```bash
   php artisan serve --working-dir=backend/
   npm run dev --prefix backend/
   ```

## Validation Steps

1. Upload `backend/db_pltte.sql`.
2. Set row counts for parent tables (`pltte_questions`, `users`) to non-zero.
3. Generate SQL data and download the output.
4. Import the generated SQL into an empty database.
5. Confirm there are no FK or unique constraint errors.

## Validation Checklist

- Parsed schema shows PK/FK/Unique/Not Null indicators for each table.
- Parent tables have non-zero row counts before generating child rows.
- Generated SQL imports with zero FK violations.
- Unique constraints are respected (no duplicate values).
