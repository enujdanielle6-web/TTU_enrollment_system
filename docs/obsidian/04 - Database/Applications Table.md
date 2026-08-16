# Applications Table

## Purpose
The most central table in the TTU system. See [[ADR-001 The Application as Term Concept]].
It tracks a student's enrollment attempt and status for a specific academic term, acting as the anchor for scheduling and finances.

## Important Columns
- `id` (PK)
- `user_id` (FK to `users`)
- `reference_number` (Unique identifier for tracking the application status via UI)
- `status` ENUM ('pending','under_review','correction_required','approved','rejected','enrolled')
- `academic_level` ENUM ('Senior High School','College')
- `grade_level` / `school_year` / `semester`
- Demographics: `address`, `guardian_name`, `medical_conditions` (Note: Often overlaps with `health_records` data)

## Relationships
- **Belongs To:** `users`
- **Has Many:** `college_enrollments` / `shs_enrollments` (maps subjects to this application)
- **Has One:** `student_assessments` (financial snapshot)
- **Has Many:** `application_documents`

## Workflows
- [[Applicant Registration Workflow]]: Creates the row.
- [[Application Review Workflow]]: Admissions modifies the `status`.
- [[Enrollment and Scheduling Workflow]]: Links subjects and sections to this row.

## Issues
- Demographic data is duplicated per application rather than residing on the `users` profile.
