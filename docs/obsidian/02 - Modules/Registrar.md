# Registrar Module

**Path**: `admin/registrar/`
**Role Required**: `registrar` or `superadmin`

The Registrar is the heaviest and most complex module in the system. It governs the entire academic structure of the university and holds the final key to the [[Enrollment Workflow]].

## Core Responsibilities
1. **Academic Management**: CRUD operations for `programs` (courses), `strands` (SHS), and `subjects`.
2. **Curriculum Building**: Grouping subjects into curricula for specific programs across year levels and semesters via `college_curriculum_builder.php`.
3. **Enrollment Finalization**: Taking applicants from the queue (who have paid their fees via [[Modules/Finance]]) and officially enrolling them into a section.
4. **Student Masterlist**: Viewing the canonical list of officially enrolled students.

## Key Files
- `curriculum.php` / `curriculum_builder.php`: Interfaces for mapping subjects to degree programs.
- `enrollment_queue.php`: The final step of the admissions process where an applicant becomes a student.

## Data Flow
The Registrar relies entirely on the [[Database Schema]]'s academic tables (`college_programs`, `college_subjects`, `college_curriculum`, `college_sections`, `college_enrollments`). When a student is finalized, a record is inserted into `college_enrollments` or `shs_enrollments`.
