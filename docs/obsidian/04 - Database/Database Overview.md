# Database Overview

The TTU MariaDB database relies heavily on relational constraints but employs a unique approach to user and student identity.

## Key Architectural Paradigm: The Application as Term
See: [[ADR-001 The Application as Term Concept]]

There is **no** `students` table.
- All users (admins, faculty, students) exist in the **[[Users Table]]**.
- A student's enrollment data for a specific academic term is stored in the **[[Applications Table]]**. 
- Enrollment subjects (`college_enrollments`, `shs_enrollments`) and financial records (`student_assessments`) are linked to the `application_id`, not the `user_id`.

## Core Tables
- **`users`**: Identity, login, roles (`enum`).
- **`applications`**: The anchor for a student's semester enrollment, containing demographic data, program/strand choices, and status.
- **`application_documents`**: Stores files and verification statuses for admission requirements tied to an application. See [[Application Documents Table]].
- **`health_records`**: Clinic health data linked to applications.
- **`college_curricula` / `shs_curricula`**: Defines the required subjects per program/strand. See [[Curriculum Architecture]].
- **`college_sections` / `shs_sections`**: Timetabled instances of curricula.
- **`fee_templates` & `payment_records` & `student_assessments`**: Financial assessment and payment tracking. See [[Assessment and Payment Workflow]].
- **`scholarships` & `scholarship_applications`**: Manages financial aid applications and programs.
- **LMS Tables**: (`lms_courses`, `lms_modules`, `lms_assignments`, `lms_submissions`, `lms_quizzes`, etc.) handling academic course delivery, grading, and files.
- **`activity_logs`**: System-wide audit trail.

## Missing / Planned Structures
- `Missing`: A robust `prerequisites` table mapping subject dependencies.

## Important Constraints
- `applications.reference_number` is UNIQUE.
- Heavy use of `ON DELETE CASCADE` implies that deleting a user or application will aggressively wipe historical records. Care must be taken.
