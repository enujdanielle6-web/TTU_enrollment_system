# 04. DATABASE ARCHITECTURE

## Database Overview
- **Database Engine:** MariaDB 10.4+ / InnoDB
- **DDL Source of Truth:** `database/schema.sql` (42 tables and views)
- **Character Set:** `utf8mb4_unicode_ci`

## Primary Entity Domains
1. **Users & Security:** `users`, `activity_logs`, `login_attempts`
2. **Academic Structure:** `college_programs`, `shs_strands`, `subjects`, `college_curricula`, `college_curriculum_subjects`, `shs_curricula`, `shs_curriculum_subjects`
3. **Sections & Schedules:** `college_sections`, `college_section_subjects`, `shs_sections`, `shs_section_subjects`
4. **Admissions & Enrollment:** `applications`, `application_documents`, `application_subject_requests`, `health_records`, `college_enrollments`, `shs_enrollments`
5. **Finance & Scholarships:** `fee_templates`, `student_assessments`, `payment_records`, `scholarships`, `scholarship_applications`, `scholarship_recipients`
6. **LMS Core:** `lms_courses`, `lms_modules`, `lms_materials`, `lms_assignments`, `lms_submissions`, `lms_quizzes`, `lms_questions`, `lms_question_choices`, `lms_quiz_attempts`, `lms_quiz_answers`, `lms_grade_items`, `lms_grades`, `lms_attendance_sessions`, `lms_attendance_records`, `lms_announcements`

## Critical Referential Rules
- `users` -> `applications` (`ON DELETE CASCADE`)
- `applications` -> `student_assessments` (`ON DELETE CASCADE`)
- `subjects` -> `college_curriculum_subjects` (`ON DELETE CASCADE`)
- `lms_courses` -> `lms_modules` / `lms_assignments` / `lms_quizzes` (`ON DELETE CASCADE`)
- `lms_courses.faculty_user_id` -> `users.id` (`ON DELETE CASCADE`) — *High cascade risk if faculty account deleted*
- `lms_courses.academic_section_id`: Logical mapping only; no foreign key constraint.
