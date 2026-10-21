# School Management System

A role-based school administration application built with PHP and PostgreSQL. It currently provides account login and dashboards for administrators, teachers, students, and parents.

## Current capabilities

- Authentication using email or a role-specific identifier.
- Administrator management for students, teachers, parents, classes, subjects, and teacher/class/subject allocations.
- Teacher views for assigned classes, subjects, and students.
- Teacher attendance recording for assigned classes, with corrections tracked in an audit history.
- Student attendance history and parent views limited to their linked children.
- Student and parent dashboards, including parent-child links and student profile details.
- Password recovery and reset pages.

Grade entry and publishing, assignments, timetables, billing, announcements, and reporting are not implemented yet. Dashboard labels for these areas are not evidence that the workflows are available.

## Technology

- PHP
- PostgreSQL through PDO
- HTML, CSS, and JavaScript

## Setup

Requirements: PHP 8.2 or later with PDO PostgreSQL enabled, and PostgreSQL 16 or later.

1. Create a PostgreSQL database and a database user with permission to create tables.
2. Copy `.env.example` to `.env` and set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`. Keep the local `.env` file private.
3. Apply the initial schema:

   ```bash
   php scripts/migrate.php
   ```

4. Create the first administrator from an interactive terminal. The password prompt is hidden and requires at least 12 characters:

   ```bash
   php scripts/create_admin.php
   ```

5. Run the database smoke tests:

   ```bash
   php tests/database_smoke.php
   ```

6. Serve the project through a PHP-compatible web server.

The initial migration is for a **new, empty database**. The migration command refuses to initialize when it sees existing application tables without migration history. For an existing installation, back up the database and review/adopt its schema before running migrations; do not point the initial migration at production data.

## Database maintenance

- SQL migrations live in `database/migrations/` and are applied in filename order.
- Applied versions are recorded in `schema_migrations`; do not edit an applied migration. Add a new numbered migration for subsequent schema changes.
- CI starts PostgreSQL, applies migrations to a fresh database, and checks core relationships and constraints.
- Teachers can record present, absent, late, or excused attendance for today or a past date, only in classes assigned to them. Corrections are recorded, and students/classes with attendance history cannot be hard-deleted.
- The initial administrator can only be created when there is no existing admin account.

## Product direction

See [docs/PRODUCT_REVIEW.md](docs/PRODUCT_REVIEW.md) for the project overview, comparison with established school platforms, recommended additions and removals, and a phased modernization roadmap.
