# Master Page-to-Code Relationship Index & File Matrix

This document provides a comprehensive, verifiable mapping between every user-facing and administrative page, its front controller route, handler controller, dependent views, database interactions, frontend JavaScript, and shared libraries.

---

## 1. Complete System File Relationship Matrix

| Page / Route URL | HTTP | Controller & Action | View Template | Key Database Tables | JavaScript / AJAX Handler | Outgoing Redirect / Transition |
|---|---|---|---|---|---|---|
| `/` | GET | `HomeController@index` | `home.php` | `announcements` | Bootstrap carousel, navbar scroll | Navigates to `/auth/login.php` or `/auth/register.php` |
| `/auth/login.php` | GET/POST | `AuthController@login` | `auth/login.php` | `users`, `login_attempts` | Inline form validation | Redirects by role to `/admin/dashboard.php`, `/applicant/dashboard.php`, etc. |
| `/auth/register.php` | GET/POST | `AuthController@register` | `auth/register.php` | `users` | Password strength meter | Redirects to `/auth/verify_email.php` |
| `/auth/verify_email.php` | GET/POST | `AuthController@verifyEmail` | `auth/verify_email.php` | `users` | 6-digit auto-tabbing, 60s cooldown timer | Redirects to `/applicant/dashboard.php` |
| `/auth/forgot_password.php` | GET/POST | `AuthController@forgotPassword` | `auth/forgot_password.php` | `users` | Email format check | Redirects to `/auth/reset_password.php` |
| `/auth/reset_password.php` | GET/POST | `AuthController@resetPassword` | `auth/reset_password.php` | `users` | Password match check | Redirects to `/auth/login.php` |
| `/auth/logout.php` | GET | `AuthController@logout` | *None* | *None* | *None* | Terminates session $\rightarrow$ `/auth/login.php` |
| `/auth/lms_student_login.php` | GET/POST | `LmsAuthController@studentLogin` | `lms/student_login.php` | `users`, `applications` | Student number auto-formatter | Redirects to `/lms/student/dashboard.php` |
| `/auth/lms_faculty_login.php` | GET/POST | `LmsAuthController@facultyLogin` | `lms/faculty_login.php` | `users` | Faculty ID validator | Redirects to `/lms/faculty/dashboard.php` |
| `/applicant/dashboard.php` | GET | `ApplicantController@dashboard` | `applicant/dashboard.php` | `applications`, `application_documents`, `health_records`, `student_assessments` | Dynamic stepper progress, alerts | Directs to form, upload, health, enroll, assessment |
| `/applicant/application_form.php` | GET/POST | `ApplicantController@form` | `applicant/application_form.php` | `applications`, `college_programs`, `shs_strands` | Address cascading dropdowns, LRN validator | Redirects to `/applicant/documents.php` |
| `/applicant/documents.php` | GET/POST | `DocumentController@index` | `applicant/documents.php` | `application_documents`, `applications` | Modal file preview, drag-and-drop | Refresh status $\rightarrow$ `/applicant/dashboard.php` |
| `/applicant/health_info.php` | GET/POST | `HealthController@index` | `applicant/health_info.php` | `health_records`, `applications` | Medical condition toggles | Redirects to `/applicant/enroll.php` |
| `/applicant/enroll.php` | GET/POST | `EnrollController@index` | `applicant/enroll.php` | `college_sections`, `college_section_subjects`, `college_enrollments`, `shs_sections`, `shs_section_subjects`, `shs_enrollments` | AJAX timetable fetcher, conflict matrix | Redirects to `/applicant/assessment.php` |
| `/applicant/assessment.php` | GET/POST | `ApplicantController@assessment` | `applicant/assessment.php` | `student_assessments`, `payment_records`, `fee_templates` | Bank proof image upload, print COM | Refresh / modal confirmation |
| `/admin/dashboard.php` | GET | `DashboardController@index` | `admin/system/dashboard.php` | `applications`, `student_assessments`, `users`, `college_enrollments`, `shs_enrollments` | Chart.js enrollment graphs | Navigates to administrative modules |
| `/admin/admissions/admissions_dashboard.php` | GET | `AdmissionsController@index` | `admin/admissions/admissions_dashboard.php` | `applications`, `application_documents` | Status filter chips, search | Opens application detail |
| `/admin/admissions/review.php` | GET | `AdmissionsController@review` | `admin/admissions/review.php` | `applications`, `users` | Tabbed filter, pagination | Opens review view |
| `/admin/admissions/application_detail.php` | GET | `AdmissionsController@detail` | `admin/admissions/application_detail.php` | `applications`, `application_documents`, `health_records`, `student_assessments`, `college_sections`, `shs_sections` | Document zoom modal, section picker | Submits to `application_process.php` |
| `/admin/admissions/application_process.php` | POST | `AdmissionsController@process` | *None* | `applications`, `users`, `college_enrollments`, `student_assessments`, `activity_logs` | Form submission / SweetAlert2 | Redirects to `/admin/admissions/review.php` |
| `/admin/clinic/clinic_dashboard.php` | GET | `ClinicController@dashboard` | `admin/clinic/clinic_dashboard.php` | `health_records`, `applications` | KPI stat badges, search bar | Navigates to clearance queue |
| `/admin/clinic/medical_clearance.php` | GET | `ClinicController@clearance` | `admin/clinic/medical_clearance.php` | `health_records`, `applications`, `users` | Filter tabs, date sorting | Opens medical detail |
| `/admin/clinic/medical_detail.php` | GET | `ClinicController@detail` | `admin/clinic/medical_detail.php` | `health_records`, `applications`, `users` | Medical history viewer | Submits to `medical_process.php` |
| `/admin/clinic/medical_process.php` | POST | `ClinicController@process` | *None* | `health_records`, `activity_logs` | Clearance verification form | Redirects to `/admin/clinic/medical_clearance.php` |
| `/admin/registrar/registrar_dashboard.php` | GET | `RegistrarController@dashboard` | `admin/registrar/registrar_dashboard.php` | `college_programs`, `shs_strands`, `subjects`, `college_curricula`, `shs_curricula` | Statistics counter widgets | Navigates to curriculum builders |
| `/admin/registrar/students.php` | GET | `RegistrarController@students` | `admin/registrar/students.php` | `users`, `applications`, `college_enrollments`, `shs_enrollments`, `student_academic_records_view` | Search, academic level filter | Submits to `students_export.php` |
| `/admin/registrar/subjects.php` | GET | `SubjectController@index` | `admin/registrar/subjects.php` | `subjects` | Add/Edit Subject Modal | Submits to `subject_process.php` |
| `/admin/registrar/college_curriculum_builder.php` | GET/POST | `CollegeController@curriculumBuilder` | `admin/registrar/college_curriculum_builder.php` | `college_curricula`, `college_curriculum_subjects`, `subjects` | Dynamic subject adder, unit counter | Redirects to curriculum index |
| `/admin/registrar/shs_curriculum_builder.php` | GET/POST | `ShsController@curriculumBuilder` | `admin/registrar/shs_curriculum_builder.php` | `shs_curricula`, `shs_curriculum_subjects`, `subjects` | Strand semester subject mapper | Redirects to SHS curriculum index |
| `/admin/scheduler/scheduler_dashboard.php` | GET | `SchedulerController@dashboard` | `admin/scheduler/scheduler_dashboard.php` | `college_sections`, `shs_sections`, `college_section_subjects`, `shs_section_subjects` | Utilization progress bars | Navigates to section builders |
| `/admin/scheduler/college_sections.php` | GET | `SchedulerController@collegeSections` | `admin/scheduler/college_sections.php` | `college_sections`, `college_programs`, `college_curricula` | Dynamic curriculum loader AJAX | Opens section schedule builder |
| `/admin/scheduler/schedule_builder.php` | GET/POST | `SchedulerController@scheduleBuilder` | `admin/scheduler/schedule_builder.php` | `college_sections`, `college_section_subjects`, `subjects` | Interactive timetable matrix grid | Submits schedule changes |
| `/admin/finance/cashier_dashboard.php` | GET | `FinanceController@dashboard` | `admin/finance/cashier_dashboard.php` | `payment_records`, `student_assessments`, `applications` | Revenue KPI cards, collection charts | Navigates to ledger / payments |
| `/admin/finance/cashier_payments.php` | GET | `FinanceController@payments` | `admin/finance/cashier_payments.php` | `payment_records`, `student_assessments`, `users` | Proof preview modal, filter tabs | Submits to `cashier_process.php` |
| `/admin/finance/cashier_receipt.php` | GET | `FinanceController@receipt` | `admin/finance/cashier_receipt.php` | `payment_records`, `student_assessments`, `applications`, `users` | Printable receipt stylesheet | Direct print view |
| `/admin/finance/fees.php` | GET | `FeeController@index` | `admin/finance/fees.php` | `fee_templates`, `college_programs`, `shs_strands` | Fee template modal builder | Submits to `fee_process.php` |
| `/admin/scholarship/scholarship_dashboard.php` | GET | `ScholarshipController@dashboard` | `admin/scholarship/scholarship_dashboard.php` | `scholarships`, `scholarship_applications`, `scholarship_recipients` | Budget utilization meters | Navigates to applications |
| `/admin/scholarship/scholarships.php` | GET | `ScholarshipController@index` | `admin/scholarship/scholarships.php` | `scholarships`, `college_programs` | Grant program editor modal | Submits to `scholarship_process.php` |
| `/admin/scholarship/scholarship_review.php` | GET/POST | `ScholarshipController@review` | `admin/scholarship/scholarship_review.php` | `scholarship_applications`, `scholarship_recipients`, `student_assessments` | Application document inspector | Approves grant & recalculates assessment |
| `/admin/system/users.php` | GET/POST | `SystemController@users` | `admin/system/users.php` | `users`, `activity_logs` | Create user modal, role permissions | Submits to `user_process.php` |
| `/admin/system/audit_logs.php` | GET | `SystemController@auditLogs` | `admin/system/audit_logs.php` | `activity_logs`, `users` | JSON old/new diff inspector modal | Filterable audit log feed |
| `/admin/system/backup.php` | GET/POST | `SystemController@backup` | `admin/system/backup.php` | *All 42 Tables (Full Schema Dump)* | File upload restore validator | Downloads `.sql` / Restores DB |
| `/admin/system/settings.php` | GET/POST | `SystemController@settings` | `admin/system/settings.php` | `system_settings`, `announcements` | Form state manager | Updates global system settings |
| `/admin/system/reports.php` | GET | `ReportController@index` | `admin/system/reports.php` | `applications`, `payment_records`, `scholarships`, `health_records` | Chart.js visual analytics | Exports CSV via `reports_export.php` |
| `/lms/student/dashboard.php` | GET | `StudentController@dashboard` | `lms/student/dashboard.php` | `college_enrollments`, `shs_enrollments`, `lms_courses`, `lms_assignments`, `lms_quizzes` | Dynamic deadline countdown, streak meter | Navigates to courses & modules |
| `/lms/student/course.php` | GET | `StudentController@course` | `lms/student/course.php` | `lms_courses`, `lms_modules`, `lms_materials` | Collapsible module accordion | Downloads material via download route |
| `/lms/student/assignments.php` | GET | `StudentAssignmentController@index` | `lms/student/assignments.php` | `lms_assignments`, `lms_submissions` | File upload dropzone, deadline status | Submits assignment file |
| `/lms/student/quizzes.php` | GET | `StudentQuizController@index` | `lms/student/quizzes.php` | `lms_quizzes`, `lms_quiz_attempts` | Timed quiz modal engine | Submits quiz answers |
| `/lms/faculty/dashboard.php` | GET | `FacultyController@dashboard` | `lms/faculty/dashboard.php` | `college_section_subjects`, `shs_section_subjects`, `lms_courses` | Class roster cards, grading queue | Navigates to course management |
| `/lms/faculty/course.php` | GET | `FacultyController@course` | `lms/faculty/course.php` | `lms_courses`, `lms_modules`, `lms_materials` | Module creator & file uploader | Publishes materials |
| `/lms/faculty/assignments.php` | GET | `FacultyAssignmentController@index` | `lms/faculty/assignments.php` | `lms_assignments`, `lms_submissions` | Submission grading modal & score input | Submits grade & remarks |
| `/lms/faculty/quizzes.php` | GET | `FacultyQuizController@index` | `lms/faculty/quizzes.php` | `lms_quizzes`, `lms_questions`, `lms_question_choices` | Question builder (MC/TF/Essay) | Publishes quiz to student portal |

---
**Related:**
- [[01 - Shared Dependencies & Impact Analysis]]
- [[02 - Cross-Module Data Flow & Table Sharing]]
- [[03 - Auth & Public Pages Relationship Map]]
- [[04 - Applicant Portal Relationship Map]]
