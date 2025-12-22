<!--
    Sync Impact Report:

    - Version change: 0.0.0 → 1.0.0
    - Added Principles:
      - I. Full-Stack Web Application
      - II. Backend: Laravel
      - III. Frontend: React via Inertia.js
      - IV. Database: Relational by Default
    - Updated Sections:
      - "Development Workflow"
      - "Testing"
      - "Governance"
    - Templates requiring updates:
      - ✅ .specify/templates/plan-template.md
      - ✅ .specify/templates/spec-template.md
      - ✅ .specify/templates/tasks-template.md
-->
# Synthetic Data Generator Constitution

## Core Principles

### I. Full-Stack Web Application
The project is a monolithic web application built with a modern, integrated technology stack. This ensures a streamlined development workflow and cohesive user experience.

### II. Backend: Laravel
The backend MUST be implemented using the Laravel PHP framework. This provides a robust, scalable, and maintainable foundation with a rich ecosystem of tools for routing, authentication, and data management.

### III. Frontend: React via Inertia.js
The frontend MUST be developed using React and seamlessly connected to the Laravel backend via Inertia.js. This approach enables the creation of a dynamic, single-page application (SPA) experience without the complexity of a separate API-driven architecture.

### IV. Database: Relational by Default
The application MUST use a relational database (e.g., MySQL, PostgreSQL) managed through Laravel's Eloquent ORM. This ensures data integrity and simplifies database interactions.

## Development Workflow
Development will follow standard Laravel and React practices. All code MUST adhere to PSR-12 for PHP and a standard JavaScript style guide (e.g., Airbnb) enforced by linters.

## Governance
All code contributions must be submitted via Pull Requests and reviewed for compliance with these principles before being merged. This constitution is the source of truth for the project's architecture and technology choices.

**Version**: 1.0.0 | **Ratified**: 2025-12-23 | **Last Amended**: 2025-12-23