# School Management System

A role-based school administration application built with PHP and PostgreSQL. It currently provides account login and dashboards for administrators, teachers, students, and parents.

## Current capabilities

- Authentication using email or a role-specific identifier.
- Administrator management for students, teachers, parents, classes, subjects, and teacher/class/subject allocations.
- Teacher views for assigned classes, subjects, and students.
- Student and parent dashboards, including parent-child links and student profile details.
- Password recovery and reset pages.

Attendance recording, grade entry and publishing, assignments, timetables, billing, announcements, and reporting are not implemented yet. Dashboard labels for these areas are not evidence that the workflows are available.

## Technology

- PHP
- PostgreSQL through PDO
- HTML, CSS, and JavaScript

## Setup

1. Configure the database connection in `.env` using the keys expected by `config/database.php`.
2. Create the database tables and seed the initial user accounts. Database schema and seed scripts are not currently included in this repository.
3. Serve the project through a PHP-compatible web server with PDO PostgreSQL enabled.
4. Keep `.env` private and never commit database credentials.

## Product direction

See [docs/PRODUCT_REVIEW.md](docs/PRODUCT_REVIEW.md) for the project overview, comparison with established school platforms, recommended additions and removals, and a phased modernization roadmap.
