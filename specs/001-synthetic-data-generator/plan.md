# Implementation Plan: Web-Based Synthetic Data Generator

**Branch**: `001-synthetic-data-generator` | **Date**: 2025-12-23 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `specs/001-synthetic-data-generator/spec.md`

## Summary

This plan outlines the implementation of a web-based synthetic data generator. The system
parses a user-provided SQL DDL file, lets the user configure data generation rules, and
then generates a downloadable SQL or CSV file. The architecture is a standard Laravel
application with Blade-rendered views and vanilla JavaScript for interactivity.

## Technical Context

**Language/Version**: PHP 8.3+, Node.js 22.x
**Primary Dependencies**: Laravel 12, Tailwind CSS, Vite, php-sql-parser, Laravel Queues
**Storage**: MySQL
**Testing**: PHPUnit (PHP)
**Target Platform**: Web (Modern Browsers)
**Project Type**: Web application
**Performance Goals**: Generate 1 million rows in under 5 minutes. Parse a 1MB DDL file in under 5 seconds.
**Constraints**: Peak memory usage during the generation of a 1-million-row dataset must be less than 512MB.
**Scale/Scope**: The system must handle complex schemas with dozens of tables and inter-dependencies.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Schema-First Workflow**: PASS. All flows begin with a DDL upload.
- **II. Relational Integrity**: PASS. Generation respects foreign keys.
- **III. Synthetic Data Safety**: PASS. Only Faker-backed data is generated.
- **IV. Transparent Job Feedback**: PASS. Job status is visible and retryable.
- **V. Blade-First Frontend**: PASS. UI uses Blade, Tailwind, and vanilla JS.

All constitution gates pass. No violations.

## Project Structure

### Documentation (this feature)

```text
specs/001-synthetic-data-generator/
|-- plan.md              # This file
|-- research.md          # Phase 0 output
|-- data-model.md        # Phase 1 output
|-- quickstart.md        # Phase 1 output
|-- contracts/           # Phase 1 output
|   |-- http-routes.md
`-- tasks.md             # Phase 2 output
```

### Source Code (repository root)

```text
backend/
|-- app/
|   |-- Http/
|   |   `-- Controllers/
|   |       |-- DataGenerationController.php
|   |       `-- SchemaController.php
|   |-- Jobs/
|   |   `-- GenerateDataJob.php
|   |-- Models/
|   |   # No Eloquent models needed for the core feature
|   `-- Services/
|       |-- DataGeneratorService.php
|       |-- SqlParserService.php
|       `-- TopologicalSortService.php
|-- resources/
|   |-- css/
|   |   `-- app.css
|   |-- js/
|   |   |-- app.js
|   |   `-- generator.js
|   `-- views/
|       |-- generator/
|       |   |-- configure.blade.php
|       |   `-- index.blade.php
|       `-- layouts/
|           `-- app.blade.php
|-- routes/
|   `-- web.php
`-- tests/
    |-- Feature/
    `-- Unit/
```

**Structure Decision**: The project uses the standard Laravel 12 structure. UI is rendered
with Blade views in `resources/views`, styling via Tailwind in `resources/css`, and
page behavior in `resources/js`. Core logic lives in `app/Services`, and background
jobs use Laravel Queues.

## Complexity Tracking

No complexity to track as no constitution violations were needed.
