# Implementation Plan: SQL Parse Accuracy

**Branch**: `001-sql-parse-accuracy` | **Date**: 2025-12-26 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/001-sql-parse-accuracy/spec.md`

## Summary

Improve SQL dump parsing and constraint-aware data generation so exported data
imports cleanly with foreign keys and unique constraints respected. The plan
focuses on accurate MySQL/MariaDB dump parsing (including ALTER TABLE constraints)
and constraint-aware generation with clear failure feedback.

## Technical Context

**Language/Version**: PHP 8.3+, Node.js 22.x  
**Primary Dependencies**: Laravel 12, Tailwind CSS, Vite, php-sql-parser, Laravel Queues  
**Storage**: MySQL  
**Testing**: PHPUnit (PHP)  
**Target Platform**: Web (Modern Browsers)  
**Project Type**: Web application  
**Performance Goals**: Parse a 1MB dump in under 5 seconds; generate 1M rows in under 5 minutes  
**Constraints**: Must support MySQL/MariaDB dumps; output must respect FK/unique rules  
**Scale/Scope**: Dozens of tables with mixed constraints and ALTER TABLE statements  

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Schema-First Workflow**: PASS. Flow begins with DDL upload and parsing.
- **II. Relational Integrity**: PASS. Data generation honors FK relationships.
- **III. Synthetic Data Safety**: PASS. Data remains synthetic and Faker-backed.
- **IV. Transparent Job Feedback**: PASS. Failure scenarios return actionable errors.
- **V. Blade-First Frontend**: PASS. No SPA frameworks introduced.

**Post-Design Re-check**: PASS. Design artifacts align with all principles.

## Project Structure

### Documentation (this feature)

```text
specs/001-sql-parse-accuracy/
|-- plan.md              # This file
|-- research.md          # Phase 0 output
|-- data-model.md        # Phase 1 output
|-- quickstart.md        # Phase 1 output
|-- contracts/           # Phase 1 output
|   `-- http-routes.md
`-- tasks.md             # Phase 2 output (/speckit.tasks)
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
|   `-- Services/
|       |-- DataGeneratorService.php
|       `-- SqlParserService.php
|-- resources/
|   |-- js/
|   |   `-- generator.js
|   `-- views/
|       `-- generator/
|-- routes/
|   `-- web.php
`-- tests/
    |-- Feature/
    `-- Unit/
```

**Structure Decision**: The existing Laravel app structure will be used. The
feature changes focus on parsing, validation, and generation services plus
frontend messaging in Blade/JS.

## Complexity Tracking

No complexity to track as no constitution violations were needed.
