# Database Overview

The TTU MariaDB database (`sia`) is a relational database containing **42 structured tables and views** governing admissions, academic catalogs, class scheduling, financial ledgers, clinic health records, and the Learning Management System.

---

## 1. Key Architectural Paradigm: The Application as Term
See: [[ADR-001 The Application as Term Concept]]

There is **no separate `students` table**.
- All human identities (applicants, students, faculty, admins, clinic, cashiers) exist in the **[[Users Table]]**.
- A student's enrollment record for an academic term is anchored to the **[[Applications Table]]**.
- Enrolled subjects (`college_enrollments`, `shs_enrollments`), clinic health data (`health_records`), and financial assessments (`student_assessments`) are linked to the `application_id`.
- A user becomes an officially enrolled student when their application transitions to `status = 'enrolled'` and they are assigned a `student_number` in `users`.

---

## 2. Table Classification Matrix (42 Tables & Views)

| Category | Table Names | Purpose |
|---|---|---|
| **Core & Security** | `users`, `activity_logs`, `login_attempts`, `system_settings`, `announcements` | Central identity, role permissions, OTP verification, security audit trail, and system parameters. |
| **Admission & Applicant** | `applications`, `application_documents`, `health_records` | Term applications, uploaded PSA/Form 137 files, medical history declarations. |
| **College Catalog** | `college_programs`, `college_curricula`, `college_curriculum_subjects`, `college_sections`, `college_section_subjects`, `college_enrollments`, `student_academic_records_view` | Degrees, versioned curricula, curriculum subjects, class sections, timetables, student course enrollments, and academic reporting view. |
| **Senior High Catalog** | `shs_strands`, `shs_curricula`, `shs_curriculum_subjects`, `shs_sections`, `shs_section_subjects`, `shs_enrollments` | Tracks, strands, versioned curricula, class sections, and student subject enrollments. |
| **Master Subjects** | `subjects` | Master registry of all teachable subjects with lecture/lab hours and unit values. |
| **Finance & Cashier** | `fee_templates`, `student_assessments`, `payment_records` | Standard fee templates (with `is_per_unit` and `semester`), student assessment statements, payment transaction ledger, and proof uploads. |
| **Scholarships** | `scholarships`, `scholarship_applications`, `scholarship_recipients`, `student_scholarships` | Financial grant programs, student applications, awards, and discount percentages. |
| **Learning Management (LMS)** | `lms_courses`, `lms_modules`, `lms_materials`, `lms_assignments`, `lms_submissions`, `lms_quizzes`, `lms_questions`, `lms_question_choices`, `lms_quiz_attempts`, `lms_quiz_answers`, `lms_attendance_sessions`, `lms_attendance_records`, `lms_announcements` | Course provision records, weekly modules, downloadable files, assignments, submissions & grading, timed quizzes, student attempts, and attendance logs. |

---

## 3. Relational Schema Reference
For full column definitions, data types, default values, primary/foreign keys, and indexes, refer to the **[[Data Dictionary]]**.

---
**Related:**
- [[Entity Relationship Architecture]]
- [[Data Dictionary]]
- [[Users Table]]
- [[Applications Table]]
- [[Curriculum Architecture]]
- [[ADR-001 The Application as Term Concept]]
