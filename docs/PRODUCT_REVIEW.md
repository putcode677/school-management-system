# Product review and modernization roadmap

## Executive overview

This repository is an early-stage, server-rendered school administration application. Its foundation is useful: separate role dashboards, account authentication, student/teacher/parent records, classes and subjects, teacher allocations, and parent-child relationships. It is not yet a complete school information system or learning management system.

The main product gap is workflow depth. Important menu items appear in dashboards, but attendance, assessment/results, assignments, timetables, billing, messaging, and reporting do not have end-to-end implementations. The database schema and migration/seed scripts are also absent from the repository, making deployment and safe change management difficult to reproduce.

## Current product inventory

| Area | Current state | Product implication |
|---|---|---|
| Accounts and roles | Login, role checks, email or role-specific IDs, password reset pages | Useful base; needs documented provisioning and stronger operational controls |
| Core records | Admin CRUD for students, teachers, parents, classes, and subjects | A credible first SIS slice |
| Class/subject allocations | Admin can assign teachers to classes and subjects | Supports basic academic organization |
| Teacher workspace | Reads assigned classes, subjects, and students | Mostly read-only; no teaching workflows yet |
| Student and parent portals | Profile/relationship information and role-specific dashboards | Portal structure exists; academic activity is not yet connected |
| Attendance, gradebook, assignments, timetable, billing, communication, reporting | Advertised in places, but no complete workflows | Must be treated as roadmap, not released functionality |
| Delivery and maintenance | No checked-in schema/migrations, automated tests, or deployment guide found in the project inventory | New installations and upgrades are hard to reproduce |

## Comparison with established platforms

This is a product-direction comparison, not a claim that the products are identical or a complete feature-by-feature audit.

| Product | Publicly documented emphasis | What this project should learn |
|---|---|---|
| [Moodle LMS](https://moodle.com/products/lms/) | A learning-management platform for delivering online learning | A school portal becomes useful when it supports real learning workflows and accessible experiences, not only record display |
| [openSIS](https://opensis.com/features) | End-to-end school operations spanning academics, administration, communication, billing, reporting, and analytics | Mature school systems connect student records to operational workflows and give staff and families consistent, trustworthy information |
| This project | Role-based records and dashboards with a small set of admin CRUD and allocation workflows | Preserve the lightweight PHP/PostgreSQL base, but close core workflow gaps before adding superficial analytics or integrations |

Large platforms also invest in reliable permissions, auditability, responsive/mobile access, integrations, data migration, and support. Those are product expectations to plan for; they should not be represented as existing capabilities here.

## What to add, in priority order

### 1. Make the foundation reproducible

- Check in versioned database migrations and safe development/demo seed data.
- Document the supported PHP/PostgreSQL versions, installation, configuration, backup, and upgrade procedures.
- Add automated tests for authentication, role boundaries, CRUD validation, and important relationship rules.
- Centralize repeated database, validation, output-escaping, and authorization patterns.
- Add CSRF protection to state-changing forms, secure session-cookie settings, rate limiting for authentication/reset flows, and a defined password-reset token lifecycle.

### 2. Complete daily school operations

- Build attendance recording by class/date, with correction history and clear attendance states.
- Add assessment setup, marks entry, calculation rules, review/approval, and controlled publication of results.
- Add timetable periods, room/teacher conflict checks, and role-based views.
- Add assignment creation/submission only if the school intends to operate an LMS; otherwise integrate with an established LMS rather than building a partial clone.
- Add fee invoices, payments, balances, receipts, and reconciliation only after local currency, fee policy, and payment-provider requirements are known.

### 3. Make family and staff communication useful

- Provide school announcements with audience targeting and publication/expiry dates.
- Show parents only their linked students' published grades, attendance, schedules, and notices.
- Add notification preferences and delivery status; avoid exposing private student information in email or push notifications.

### 4. Make the system operable at school scale

- Add search, pagination, filters, safe bulk import/export, and duplicate detection for large rosters.
- Add audit events for sensitive changes and explicit least-privilege permissions.
- Add a responsive, accessible design system, keyboard navigation, visible focus, and localization/time-zone/currency support.
- Plan integrations through documented interfaces after core data ownership and synchronization rules are defined.

## What to remove or change

- Remove `href="#"` controls and menu items that pretend to be working destinations. Hide them until implemented, or label them clearly as unavailable.
- Remove hard-coded zeroes and sample-looking KPI values. Show a real value, a loading/error state, or no metric.
- Remove roadmap claims from the list of implemented modules in documentation.
- Avoid expanding the current large, duplicated inline dashboard styles. Extract shared layout, navigation, form, and table styles as screens are refactored.
- Do not add charts, AI features, or payment integrations before the underlying records, policies, and reporting definitions are reliable.

## Suggested delivery sequence

1. **Stabilize:** schema/migrations, setup guide, security baseline, automated tests, and accurate capability labels.
2. **Complete SIS essentials:** attendance and gradebook with approval/publication, then usable timetable and school-year/class enrollment.
3. **Improve family communication:** announcements and parent-facing published academic information.
4. **Scale responsibly:** import/export, audit logs, reporting, localization, accessibility, and mobile-friendly workflows.
5. **Integrate selectively:** LMS, payment, identity, and notification providers based on confirmed school requirements.

Each phase should have acceptance criteria and be released only when permissions, validation, error handling, and tests cover the full workflow.
