# LMS ADMIN PRE-IMPLEMENTATION ARCHITECTURE AUDIT

**Target System**: Triple T University (TTU) Academic & Learning Management System  
**Audit Scope**: LMS Subsystem, Enrollment Integration, Registrar/Scheduler Domain Boundaries, LMS Admin Readiness  
**Evaluation Role**: Senior Systems Architect & LMS Domain Analyst  
**Audit Date**: September 30, 2026  
**Authoritative Root**: `c:\xampp\htdocs\sia`  
**Evaluation Standard**: Zero Code Modifications — Read-Only Architectural Forensic Audit  

---

## 1. EXECUTIVE SUMMARY

This pre-implementation architecture audit provides an authoritative, code-level forensic evaluation of the **Learning Management System (LMS)** and its boundary with the **Enrollment Management Subsystem** at Triple T University (TTU).

### Foundational Architectural Mandate
The core governing principle across the entire TTU codebase is:
$$\mathbf{ENROLLMENT\ OWNS\ ACADEMIC\ TRUTH.}$$
$$\mathbf{LMS\ OWNS\ THE\ LEARNING\ EXPERIENCE.}$$

The Enrollment Subsystem (Admissions, Clinic, Cashier, Registrar, and Scheduler) is the sole authoritative source of truth for student identity, degree programs, curriculum versioning, master subjects, timetable schedules, section cohorts, and official matriculation. The LMS is a downstream instructional delivery platform that projects, isolates, and enriches course offerings without mutating or duplicating foundational registrar records.

### Forensic Finding: LMS Admin Implementation Status
Prior project audits and handoff documents presented conflicting evaluations regarding LMS Admin capabilities. A forensic inspection of the active codebase reveals:
1. **LMS Administration Backend Exists**: Contrary to older documentation claims (`docs/obsidian/LMS_FEATURE_AUDIT.md`) stating that LMS Admin dashboards, course catalogs, user management, and term archival were completely missing, these capabilities were implemented during Phase 5 under [LmsAdminController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php) (478 lines) and [LmsAdminService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php) (883 lines).
2. **Thirteen Administrative Routes Registered**: [app/Routes/web.php](file:///c:/xampp/htdocs/sia/app/Routes/web.php#L141-L161) registers 13 operational `/admin/lms/*` endpoints covering dashboard KPIs, course catalog search, deep course inspection, faculty reassignment, course status toggling, course generation, user access status management, enrollment synchronization diagnostics, deterministic reconciliation, academic term bulk archival, and LMS audit logging.
3. **Dedicated Presentation Layer**: Five administrative views exist in `app/Views/admin/lms/` (`dashboard.php`, `courses/index.php`, `courses/detail.php`, `users/index.php`, `sync/index.php`, `archive/index.php`, `audit_logs/index.php`).
4. **Critical Gaps & Architectural Fragilities**:
   - **Navigation Omission**: While [app/Views/components/admin_navbar.php](file:///c:/xampp/htdocs/sia/app/Views/components/admin_navbar.php#L401-L403) links to `/sia/admin/lms/dashboard`, the standalone administrative sidebar ([app/Views/components/sidebar.php](file:///c:/xampp/htdocs/sia/app/Views/components/sidebar.php)) completely omits LMS Governance.
   - **Role Escalation Vulnerability in `enforceAdminAccess()`**: In [LmsAdminController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php#L38-L44), the controller falls back to `requirePermission(['programs.manage', 'curriculum.manage', 'sections.manage', 'users.manage'])`. Because [hasPermission()](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php#L672) returns `true` if *any* permission in the array matches, users with role `scheduler` (who possess `sections.manage`) bypass the check and gain full control over LMS Admin operations.
   - **No Dedicated `lms_admin` Role**: In [database/schema.sql](file:///c:/xampp/htdocs/sia/database/schema.sql#L1264), the `users.role` enum only defines `superadmin`, `admin`, `admissions`, `scholarship`, `cashier`, `clinic`, `faculty`, `scheduler`, `applicant`, and `student`. LMS administrative governance is currently bundled into general `admin`/`superadmin`.
   - **Ambiguous Conflict Handling**: While deterministic auto-reconciliation successfully generates missing course shells and aligns mismatched faculty, legacy duplicate shells and orphan courses cannot be resolved or merged via the UI.
   - **Documentation Drift**: Multiple documentation files in `docs/obsidian/` describe obsolete 3-tier hierarchies (`lms_lessons`), obsolete tables (`lms_course_resources`, `lms_student_progress`), and outdated feature statuses.

---

## 2. EXISTING LMS ARCHITECTURE

The LMS subsystem adheres to the **Hybrid MVC + Domain Service Layer** architectural pattern mandated by TTU system guidelines.

```
c:\xampp\htdocs\sia\
├── app/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   └── LmsAdminController.php             [IMPLEMENTED] - Governance, catalog, sync, archival
│   │   └── Lms/
│   │       ├── DownloadController.php             [IMPLEMENTED] - Secure streaming of materials & submissions
│   │       ├── FacultyAnnouncementController.php  [IMPLEMENTED] - Faculty announcement CRUD
│   │       ├── FacultyAssignmentController.php    [IMPLEMENTED] - Assignment authoring, submission grading
│   │       ├── FacultyAttendanceController.php    [IMPLEMENTED] - Attendance session logging & roster marking
│   │       ├── FacultyCalendarController.php      [IMPLEMENTED] - Faculty academic deadlines calendar
│   │       ├── FacultyController.php              [IMPLEMENTED] - Faculty teaching dashboard, modules, materials
│   │       ├── FacultyGradebookController.php     [IMPLEMENTED] - Course-wide gradebook calculation grid
│   │       ├── FacultyQuizController.php          [IMPLEMENTED] - Quiz builder, question bank, results review
│   │       ├── LmsAuthController.php              [IMPLEMENTED] - Dedicated student & faculty login portals
│   │       ├── StudentAnnouncementController.php  [IMPLEMENTED] - Student course announcement viewer
│   │       ├── StudentAssignmentController.php    [IMPLEMENTED] - Student assignment submitter
│   │       ├── StudentAttendanceController.php    [IMPLEMENTED] - Student personal attendance log
│   │       ├── StudentCalendarController.php      [IMPLEMENTED] - Student deadline calendar
│   │       ├── StudentController.php              [IMPLEMENTED] - Student dashboard, course view, syllabus
│   │       ├── StudentGradebookController.php     [IMPLEMENTED] - Student personal gradebook breakdown
│   │       └── StudentQuizController.php          [IMPLEMENTED] - Timed quiz taker & instant scoring
│   ├── Services/
│   │   ├── LmsService.php                         [IMPLEMENTED] - Course shell resolution, roster, permissions
│   │   ├── LmsAdminService.php                    [IMPLEMENTED] - Admin KPIs, sync diagnostics, reconcile, archive
│   │   ├── LmsGradebookService.php                [IMPLEMENTED] - Formative grade calculations
│   │   ├── LmsQuizService.php                     [IMPLEMENTED] - Quiz timing, question choices, auto-evaluator
│   │   ├── LmsAttendanceService.php               [IMPLEMENTED] - Attendance roll management
│   │   ├── LmsAnnouncementService.php             [IMPLEMENTED] - Announcement broadcasting logic
│   │   └── LmsCalendarService.php                 [IMPLEMENTED] - Deadline date aggregation
│   ├── Repositories/
│   │   ├── EnrollmentRepositoryInterface.php      [IMPLEMENTED] - Contract for course resolution
│   │   ├── CollegeEnrollmentRepository.php        [IMPLEMENTED] - College dynamic course query
│   │   └── ShsEnrollmentRepository.php            [IMPLEMENTED] - SHS dynamic course query
│   └── Views/
│       ├── admin/lms/                             [IMPLEMENTED] - 7 administrative governance views
│       ├── lms/faculty/                           [IMPLEMENTED] - 21 faculty instruction views
│       └── lms/student/                           [IMPLEMENTED] - 19 student learning views
└── storage/
    └── uploads/lms/
        ├── materials/                             [IMPLEMENTED] - Downloadable course files
        └── submissions/                           [IMPLEMENTED] - Student assignment uploads
```

---

## 3. EXISTING LMS ADMIN FUNCTIONALITY

Forensic analysis of [LmsAdminController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php) and [LmsAdminService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php) reveals the exact capabilities currently implemented:

| Method | Endpoint | Handler | Database Interaction | Operational Status |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/lms/dashboard` | `LmsAdminController@dashboard` | Queries `lms_courses`, `users`, `lms_assignments`, `lms_submissions`, `lms_quizzes`, `lms_quiz_attempts`, `college_sections`, `activity_logs` | **IMPLEMENTED** — Aggregates operational KPIs, active term, course counts, and recent audit activity. |
| `GET` | `/admin/lms/courses` | `LmsAdminController@courses` | Queries `lms_courses`, `subjects`, `college_sections`, `shs_sections`, `users` | **IMPLEMENTED** — Filterable, paginated course catalog supporting search by code/title/instructor, academic level filter, and faculty assignment status. |
| `GET` | `/admin/lms/courses/{id}` | `LmsAdminController@courseDetail` | Queries `lms_courses`, `college_section_subjects`, `shs_section_subjects`, `lms_modules`, `lms_materials`, `lms_assignments`, `lms_quizzes`, `college_enrollments`, `shs_enrollments`, `users` | **IMPLEMENTED** — Deep inspection dossier assembling shell metadata, timetable schedule, regular/irregular roster, modules, assignments, quizzes, and eligible faculty list. |
| `POST` | `/admin/lms/courses/{id}/reassign` | `LmsAdminController@reassignFaculty` | Updates `lms_courses.faculty_user_id` and optionally synchronizes `college_section_subjects.faculty_user_id` / `shs_section_subjects.faculty_user_id` | **IMPLEMENTED** — Atomic instructor transfer inside PDO transaction; writes audit log to `activity_logs`. |
| `POST` | `/admin/lms/courses/{id}/status` | `LmsAdminController@updateCourseStatus` | Updates `lms_courses.status` (`active` $\leftrightarrow$ `archived`) | **IMPLEMENTED** — Toggles course state while retaining all modules, materials, submissions, and attempts. |
| `GET` | `/admin/lms/generator` | `LmsAdminController@courseGenerator` | Selects unmapped `college_section_subjects` / `shs_section_subjects` without `lms_courses` | **IMPLEMENTED** — Interface to provision missing shells manually. |
| `POST` | `/admin/lms/generate` | `LmsAdminController@generateLmsCourse` | Inserts into `lms_courses` with duplicate protection | **IMPLEMENTED** — Idempotent course creation; logs activity. |
| `GET` | `/admin/lms/users` | `LmsAdminController@users` | Queries `users` with subqueries for active course loads | **IMPLEMENTED** — Paginated user directory with role, LMS status, and course counts. |
| `POST` | `/admin/lms/users/{id}/status` | `LmsAdminController@updateUserStatus` | Updates `users.lms_status` (`active`, `suspended`, `inactive`) | **IMPLEMENTED** — Toggles LMS access without altering `applications.status` or official enrollment records. |
| `GET` | `/admin/lms/sync` | `LmsAdminController@sync` | Diagnostic scan comparing timetable section-subjects against `lms_courses` | **IMPLEMENTED** — Identifies missing shells, faculty mismatches, duplicate shells, and orphan courses. |
| `POST` | `/admin/lms/sync/reconcile` | `LmsAdminController@reconcile` | Inserts missing `lms_courses` and aligns faculty to timetable | **IMPLEMENTED** — Deterministic, transaction-safe auto-reconciliation. Ambiguous duplicates and orphans are flagged but left untouched. |
| `GET` | `/admin/lms/archive` | `LmsAdminController@archive` | Queries archived courses and distinct academic terms | **IMPLEMENTED** — Term archival overview. |
| `POST` | `/admin/lms/archive/term` | `LmsAdminController@processArchiveTerm` | Updates `lms_courses.status = 'archived'` for all sections in term | **IMPLEMENTED** — Non-destructive bulk term archival. |
| `GET` | `/admin/lms/audit_logs` | `LmsAdminController@auditLogs` | Queries `activity_logs` scoped to LMS operations | **IMPLEMENTED** — Paginated LMS audit history. |

---

## 4. ENROLLMENT ARCHITECTURE

### The Application as Term Concept (ADR-001)
The TTU system enforces strict domain boundaries governed by Architectural Decision Records:
1. **No `students` Table**: There is no dedicated table named `students`. Every person in the system is a record in the `users` table ([database/schema.sql#L1252](file:///c:/xampp/htdocs/sia/database/schema.sql#L1252)).
2. **Applications Define Academic Enrollment**: A student's academic existence in any given semester is defined by an `applications` record ([database/schema.sql#L72](file:///c:/xampp/htdocs/sia/database/schema.sql#L72)).
3. **Application State Machine**:
   $$\text{draft} \longrightarrow \text{submitted} \longrightarrow \text{under\_review} \longrightarrow \text{approved} \longrightarrow \text{payment\_verified} \longrightarrow \mathbf{enrolled}$$
   * `Admissions`: Marks `status = 'approved'` and assigns initial section/curriculum. Cannot confer official enrollment.
   * `Clinic`: Verifies medical clearance (`health_records.status = 'verified'`). Mandatory gate before admissions approval.
   * `Cashier`: Verifies tuition payment, transitioning application to `status = 'payment_verified'`. Cannot mark `status = 'enrolled'`.
   * `Registrar`: Holds exclusive institutional authority to finalize enrollment (`status = 'enrolled'`) via [EnrollmentService::finalizeEnrollment()](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php#L25).
4. **Enrolled Subject Storage**:
   * College students: Enrolled subjects are stored in [college_enrollments](file:///c:/xampp/htdocs/sia/database/schema.sql#L377) linked to `application_id`, `subject_id`, and `college_section_id`.
   * Senior High School students: Enrolled subjects are stored in [shs_enrollments](file:///c:/xampp/htdocs/sia/database/schema.sql#L349) linked to `application_id`, `subject_id`, and `shs_section_id`.
   * Lifecycle statuses: `'enrolled'`, `'dropped'`, `'withdrawn'`.

---

## 5. REGISTRAR RESPONSIBILITIES

The Registrar module ([app/Controllers/Admin/Registrar/RegistrarController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/RegistrarController.php)) owns:
1. **Master Subject Catalog (`subjects`)**: Code, title, lecture/lab units, education level, and active status. Subjects are protected by foreign key constraints (`ON DELETE RESTRICT`).
2. **Degree Programs & Strands (`college_programs`, `shs_strands`)**: Institutional academic offerings and landing page card metadata.
3. **Curriculum Lifecycle & Immutability (`college_curricula`, `shs_curricula`)**: Versioning (`draft` $\rightarrow$ `active` $\rightarrow$ `archived`). Active curricula are permanently locked; modifications require cloning to a draft version ($v+1$) per `ADR-005`.
4. **Exclusive Enrollment Finalization Authority**: Via [EnrollmentService::finalizeEnrollment()](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php#L25):
   - Generates race-free official Student Numbers (`YYYY-XXXXXX`) via atomic sequences in [StudentNumberService.php](file:///c:/xampp/htdocs/sia/app/Services/StudentNumberService.php).
   - Provisions institutional email (`first.last@ttu.edu.ph`).
   - Updates `users.role = 'student'` and `users.lms_status = 'active'`.
   - Transitions `applications.status = 'enrolled'`.
   - Provisions student section subjects into `college_enrollments` / `shs_enrollments`.
5. **Student Masterlist**: Official roster of enrolled students ([students.php](file:///c:/xampp/htdocs/sia/app/Views/admin/registrar/students.php)).
6. **Section Transfers & Subject Dropping**: Controls student transfers via `RegistrarController@transferSection` and `dropSubject`, modifying authoritative enrollment rows.

---

## 6. SCHEDULER RESPONSIBILITIES

The Scheduler module ([app/Controllers/Admin/Scheduler/SchedulerController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php)) owns:
1. **Section Provisioning**: Creates and names cohort blocks in `college_sections` and `shs_sections` (e.g. `BSIT 1-A`, `STEM 11-1`).
2. **Timetable Matrix & Logistics**: Attaches subjects to sections via `college_section_subjects` and `shs_section_subjects`, defining:
   - Days of the week (`day`)
   - Start and end times (`start_time`, `end_time`)
   - Physical or virtual rooms (`room`)
   - Delivery modes (`Face-to-Face`, `Online Synchronous`, `Blended`, `Asynchronous`)
   - Assigned faculty instructor (`faculty_user_id` and `instructor` string)
3. **Conflict Detection**: Detects room double-bookings and instructor schedule collisions in `SchedulerController::process` ([lines 700-759](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php#L700-L759)).
4. **Faculty Workload Governance**: Monitors instructor teaching loads and unit limits via `faculty_profiles`, `faculty_specializations`, and `faculty_workloads_view`.
5. **Automated LMS Course Provisioning**: When saving timetable schedules ([SchedulerController.php:771](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php#L771)), the scheduler automatically inserts or updates corresponding rows in `lms_courses`:
   ```sql
   INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
   VALUES (?, ?, ?, ?, 'active')
   ON DUPLICATE KEY UPDATE faculty_user_id = VALUES(faculty_user_id), status = 'active';
   ```

---

## 7. FACULTY RESPONSIBILITIES

Within the LMS learning space, instructors assigned to course shells own:
1. **Course Modules**: Creating chapters/units in `lms_modules`.
2. **Learning Materials**: Uploading lecture slides, reading PDFs, and documents into `lms_materials` (saved in `storage/uploads/lms/materials/`).
3. **Formative Assignments**: Authoring assignment prompts, due dates, and grading criteria in `lms_assignments`.
4. **Submission Grading & Feedback**: Evaluating student work in `lms_submissions`, entering numeric grades, and providing feedback comments.
5. **Quizzes & Tests**: Authoring online assessments in `lms_quizzes`, managing question banks in `lms_questions`, and defining options in `lms_question_choices`.
6. **Attendance Management**: Scheduling class meetings in `lms_attendance_sessions` and marking student roll states (`present`, `absent`, `late`, `excused`) in `lms_attendance_records`.
7. **Announcements**: Broadcasting course bulletins in `lms_announcements`.
8. **Class Gradebook**: Viewing aggregate performance in `FacultyGradebookController`.

---

## 8. STUDENT RESPONSIBILITIES

Enrolled learners interact with the LMS strictly as consumers and submitters:
1. **Course Navigation**: Reviewing assigned courses and schedules via [my_courses.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/my_courses.php).
2. **Material Access**: Downloading instructional documents published by faculty.
3. **Task Completion**: Submitting files and text deliverables in [StudentAssignmentController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAssignmentController.php).
4. **Timed Assessments**: Taking online quizzes via [StudentQuizController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentQuizController.php) with client-side countdown timer and server-side evaluation.
5. **Progress Monitoring**: Reviewing personal grades, attendance percentages, announcements, and calendar deadlines.

---

## 9. DATABASE / DATA OWNERSHIP ANALYSIS

### Definitive Data Ownership Matrix

| Table Name | Primary Purpose | Authoritative Owner | Created By | Updated By | Consumed By | Relationship to Enrollment | Relationship to LMS | Source of Truth |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `users` | User accounts, credentials, institutional emails, and LMS access state | **System Admin / Enrollment** | AuthController / SystemController | User profile, EnrollmentService, LmsAdminService | All modules | Core user identity table | Authenticates student & faculty; provides `lms_status` | **ENROLLMENT** |
| `applications` | Term matriculation, level, grade, program, section, and status | **Admissions / Registrar** | ApplicantController | Admissions, Cashier, Registrar | All modules | Primary anchor for term enrollment | Gating check: `applications.status = 'enrolled'` | **ENROLLMENT** |
| `college_programs` | Degree program masterlist | **Registrar** | CollegeController | CollegeController | Admissions, Registrar, Views | Program definitions | Program metadata displayed on course cards | **REGISTRAR** |
| `shs_strands` | Senior High School strand masterlist | **Registrar** | ShsController | ShsController | Admissions, Registrar, Views | Strand definitions | Strand metadata displayed on course cards | **REGISTRAR** |
| `subjects` | Universal academic subject catalog | **Registrar** | SubjectController | SubjectController | Scheduler, Registrar, LMS | Master subject registry | Linked via `lms_courses.subject_id` | **REGISTRAR** |
| `college_curricula` | Curriculum versions & academic plans | **Registrar** | CollegeController | CollegeController | Scheduler, Admissions | Course requirement blueprint | Determines curriculum structure | **REGISTRAR** |
| `college_sections` | College section cohorts | **Scheduler** | SchedulerController | SchedulerController | Admissions, Registrar, LMS | Section definition | Logical FK: `lms_courses.academic_section_id` | **SCHEDULER** |
| `shs_sections` | SHS section cohorts | **Scheduler** | SchedulerController | SchedulerController | Admissions, Registrar, LMS | Section definition | Logical FK: `lms_courses.academic_section_id` | **SCHEDULER** |
| `college_section_subjects` | College timetable matrix & faculty bindings | **Scheduler** | SchedulerController | SchedulerController, LmsAdminService | Scheduler, LMS Repositories | Official schedule & teacher | Authoritative source for `lms_courses.faculty_user_id` | **SCHEDULER** |
| `shs_section_subjects` | SHS timetable matrix & faculty bindings | **Scheduler** | SchedulerController | SchedulerController, LmsAdminService | Scheduler, LMS Repositories | Official schedule & teacher | Authoritative source for `lms_courses.faculty_user_id` | **SCHEDULER** |
| `college_enrollments` | Official college student enrolled subjects | **Registrar** | EnrollmentService | EnrollmentService | Registrar, LMS Repositories | Authoritative subject enrollment | Directly queried to authorize student LMS access | **REGISTRAR** |
| `shs_enrollments` | Official SHS student enrolled subjects | **Registrar** | EnrollmentService | EnrollmentService | Registrar, LMS Repositories | Authoritative subject enrollment | Directly queried to authorize student LMS access | **REGISTRAR** |
| `application_subject_requests` | Custom subject requests for irregular students | **Admissions / Registrar** | ApplicantController / AdmissionsController | AdmissionsController | EnrollmentService, AssessmentService | Overrides default section subjects | Enrolls irregulars into specific subject sections | **REGISTRAR** |
| `lms_courses` | Instructional classroom instances | **LMS Subsystem** | SchedulerController, EnrollmentService, LmsAdminService | LmsAdminService, SchedulerController | All LMS Controllers | Derived downstream from section subjects | Central container linking modules, tasks, and grades | **LMS** |
| `lms_modules` | Course chapters and instructional units | **Faculty** | FacultyController | FacultyController | StudentController, FacultyController | None | Attached to `lms_courses.id` | **LMS** |
| `lms_materials` | Uploaded instructional documents | **Faculty** | FacultyController | FacultyController | DownloadController | None | Attached to `lms_modules.id` | **LMS** |
| `lms_assignments` | Assessment tasks, homework, deliverables | **Faculty** | FacultyAssignmentController | FacultyAssignmentController | StudentAssignmentController | None | Attached to `lms_courses.id` | **LMS** |
| `lms_submissions` | Student assignment work and faculty grades | **Student / Faculty** | StudentAssignmentController | FacultyAssignmentController | GradebookService, DownloadController | None | Attached to `lms_assignments.id` + `users.id` | **LMS** |
| `lms_quizzes` | Timed quizzes and exams | **Faculty** | FacultyQuizController | FacultyQuizController | StudentQuizController | None | Attached to `lms_courses.id` | **LMS** |
| `lms_questions` | Quiz questions | **Faculty** | FacultyQuizController | FacultyQuizController | LmsQuizService | None | Attached to `lms_quizzes.id` | **LMS** |
| `lms_question_choices` | Multiple choice and true/false options | **Faculty** | FacultyQuizController | FacultyQuizController | LmsQuizService | None | Attached to `lms_questions.id` | **LMS** |
| `lms_quiz_attempts` | Student test-taking attempt sessions | **Student / LMS Engine** | StudentQuizController | LmsQuizService | FacultyQuizController, GradebookService | None | Attached to `lms_quizzes.id` + `users.id` | **LMS** |
| `lms_quiz_answers` | Student answers per attempt question | **Student / LMS Engine** | StudentQuizController | LmsQuizService | FacultyQuizController | None | Attached to `lms_quiz_attempts.id` | **LMS** |
| `lms_attendance_sessions` | Class meeting dates | **Faculty** | FacultyAttendanceController | FacultyAttendanceController | StudentAttendanceController | None | Attached to `lms_courses.id` | **LMS** |
| `lms_attendance_records` | Individual student roll marks | **Faculty** | FacultyAttendanceController | FacultyAttendanceController | StudentAttendanceController | None | Attached to session + `users.id` | **LMS** |
| `lms_announcements` | Course announcements | **Faculty / LMS Admin** | FacultyAnnouncementController | FacultyAnnouncementController | StudentAnnouncementController | None | Attached to `lms_courses.id` | **LMS** |
| `activity_logs` | Institutional audit trail | **System Security** | Helper `logActivity()` | Never updated (append-only) | SystemController, LmsAdminService | Cross-cutting audit trail | Records administrative LMS mutations | **SECURITY** |

---

## 10. ENROLLMENT ↔ LMS DATA FLOW

```text
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                 ENROLLMENT DOMAIN                                      │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ 1. APPLICANT MATRICULATION:                                                            │
│    Applicant submits registration -> Admissions assigns Section & Curriculum ->        │
│    Clinic issues clearance -> Cashier verifies payment ->                              │
│    Registrar executes EnrollmentService::finalizeEnrollment().                        │
│                                                                                        │
│ 2. IDENTITY CREATION:                                                                  │
│    Student Number assigned (YYYY-XXXXXX via StudentNumberService).                     │
│    Institutional Email provisioned (first.last@ttu.edu.ph).                            │
│    User role updated: users.role = 'student', users.lms_status = 'active'.             │
│    Application finalized: applications.status = 'enrolled'.                            │
│                                                                                        │
│ 3. SUBJECT ALLOCATION:                                                                 │
│    Regular students: Section subjects copied into college_enrollments / shs_enrollments.│
│    Irregular students: Custom approved subjects copied from application_subject_requests.│
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                             Authoritative Timetable Sync
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                 SCHEDULING DOMAIN                                      │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ 4. TIMETABLE & FACULTY BINDING:                                                        │
│    Scheduler builds timetable in SchedulerController::process.                         │
│    Assigns faculty_user_id, day, start_time, end_time, room, delivery_mode.             │
│    Automated LMS Course Provisioning runs:                                             │
│    INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, ...)      │
│    ON DUPLICATE KEY UPDATE faculty_user_id = VALUES(faculty_user_id), status = 'active'│
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                                  Deterministic Shell Link
                                            │
                                            ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                              LMS DERIVATION BOUNDARY                                   │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ 5. DYNAMIC STUDENT COURSE RESOLUTION:                                                  │
│    Student logs in -> CollegeEnrollmentRepository::getActiveStudentCourses(userId).    │
│    Live JOIN: college_enrollments (status='enrolled') JOIN lms_courses                      │
│               ON lms_courses.academic_section_id = college_enrollments.college_section_id│
│              AND lms_courses.subject_id = college_enrollments.subject_id.              │
│    * NO duplicate 'lms_enrollments' table is created or queried.                       │
│                                                                                        │
│ 6. DYNAMIC FACULTY COURSE RESOLUTION:                                                  │
│    Faculty logs in -> LmsService::getFacultyCourses(facultyUserId).                    │
│    Live query: lms_courses WHERE faculty_user_id = :fid AND status = 'active'.         │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

### Multi-Section Instance Isolation (ADR-011)
When two sections take the same subject (e.g. `BSIT 1-A` and `BSIT 1-B` both taking `CC101` Introduction to Computing):
- `lms_courses` enforces `UNIQUE KEY unique_section_subject_course (academic_level, academic_section_id, subject_id)`.
- Section 1-A receives Course ID `1`; Section 1-B receives Course ID `2`.
- Module contents, assignment uploads, student submissions, quiz questions, and gradebooks are completely isolated. Zero crosstalk exists.

---

## 11. RESPONSIBILITY MATRIX

| Capability | Enrollment | Registrar | Scheduler | LMS Admin | Faculty | Student | Justification Based on Implementation |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| **Student Creation** | **OWNER** | Approver | — | — | — | Initiator | Public registration creates applicant account via deferred OTP ([ADR-006](file:///c:/xampp/htdocs/sia/docs/obsidian/14%20-%20Architecture%20Decisions/ADR-006%20Deferred%20Account%20Creation%20via%20OTP.md)). |
| **Student Account Credentials** | Participant | **OWNER** | — | — | — | Consumer | Registrar issues student number and institutional email via `EnrollmentService`. |
| **Student Official Enrollment** | Gatekeeper | **OWNER** | — | — | — | Applicant | Registrar holds exclusive institutional authority to finalize `applications.status = 'enrolled'`. |
| **Curriculum Catalog** | — | **OWNER** | Consumer | — | — | — | Registrar maintains `subjects` and `college_curricula` with versioning immutability ([ADR-005](file:///c:/xampp/htdocs/sia/docs/obsidian/14%20-%20Architecture%20Decisions/ADR-005%20Curriculum%20Versioning%20and%20Subject%20Catalog%20Immutability.md)). |
| **Subject Catalog** | — | **OWNER** | Consumer | — | — | — | Universal catalog in `subjects`; foreign keys lock active catalog items from deletion. |
| **Section Cohort Creation** | Consumer | Consumer | **OWNER** | — | — | — | Scheduler creates class sections in `college_sections` / `shs_sections`. |
| **Faculty Assignment** | — | — | **OWNER** | Synchronizer | — | — | Scheduler assigns instructors in timetable; LMS Admin can sync reassignment if authorized. |
| **Timetable Schedule** | Consumer | Consumer | **OWNER** | — | — | Consumer | Scheduler sets days, times, and rooms; LMS reads timetable blocks live for course cards. |
| **LMS Account Access State** | Consumer | Activator | — | **OWNER** | — | Consumer | `EnrollmentService` activates upon enrollment; LMS Admin toggles `users.lms_status` (`active` $\leftrightarrow$ `suspended`). |
| **LMS Course Shell Provisioning** | Consumer | Consumer | Automated | **OWNER** | — | — | Created via Scheduler upsert, EnrollmentService regular/irregular provisioning, or LMS Admin generator/sync. |
| **LMS Course Activation** | — | — | Initiator | **OWNER** | — | — | Status is `'active'` upon creation; LMS Admin can restore archived shells to active. |
| **LMS Course Archival** | — | — | — | **OWNER** | — | — | LMS Admin bulk-archives completed terms in `LmsAdminService::archiveTerm()`. |
| **LMS Course Access Gating** | — | Authorizer | — | Manager | — | Consumer | Dynamic resolution via `college_enrollments` / `shs_enrollments` where `status = 'enrolled'`. |
| **Learning Materials** | — | — | — | Inspector | **OWNER** | Consumer | Faculty upload files attached to modules; LMS Admin has read-only inspection access. |
| **Lessons / Units** | — | — | — | Inspector | **OWNER** | Consumer | Faculty author units in `lms_modules` (`lms_lessons` does not exist). |
| **Assignments** | — | — | — | Inspector | **OWNER** | Submitter | Faculty author prompts and deadlines; students upload submissions. |
| **Quizzes & Tests** | — | — | — | Inspector | **OWNER** | Taker | Faculty author questions; LMS auto-evaluates scores upon submission. |
| **Formative Task Grades** | — | Consumer | — | Inspector | **OWNER** | Consumer | Faculty score submissions; student gradebook computes formative progress dynamically. |
| **Student Progress** | — | — | — | Monitor | Monitor | **OWNER** | Calculated dynamically from task completion percentages; no static tracker table exists. |
| **LMS Settings** | — | — | — | **OWNER** | — | — | Configured in `system_settings` or LMS Admin dashboard parameters. |
| **LMS Permissions** | — | — | — | **OWNER** | — | — | Granular JSON permissions in `users.permissions` (`lms.manage`, `lms.courses.manage`). |
| **LMS Operational Reports** | — | Consumer | Consumer | **OWNER** | — | — | LMS Admin compiles course distribution, submission statistics, and faculty assignment metrics. |
| **LMS Audit Logging** | Consumer | Consumer | Consumer | **OWNER** | — | — | LMS Admin actions recorded in `activity_logs` (`bi-mortarboard`, `bi-person-badge`, `bi-archive`). |

---

## 12. LMS ADMIN GAP ANALYSIS

### Existing Functionality (What Already Exists in Code)
1. Operational KPIs and metrics dashboard (`/admin/lms/dashboard`).
2. Paginated, filterable course catalog (`/admin/lms/courses`).
3. Deep course shell inspection with live timetable, roster, modules, assignments, quizzes (`/admin/lms/courses/{id}`).
4. Synchronized faculty reassignment updating both `lms_courses` and timetable section-subjects (`/admin/lms/courses/{id}/reassign`).
5. Course status toggling (`active` $\leftrightarrow$ `archived`) preserving student artifacts (`/admin/lms/courses/{id}/status`).
6. Unmapped course shell generator (`/admin/lms/generator` and `/admin/lms/generate`).
7. User access management directory and status toggling (`active`, `suspended`, `inactive`) (`/admin/lms/users`).
8. Enrollment synchronization diagnostic scan (`/admin/lms/sync`).
9. Safe, deterministic reconciliation provisioning missing shells and aligning faculty drift (`/admin/lms/sync/reconcile`).
10. Academic term bulk archival by level, year, and semester (`/admin/lms/archive` and `/admin/lms/archive/term`).
11. LMS-specific administrative audit log viewer (`/admin/lms/audit_logs`).

### Missing Functionality (Genuinely Needed)
1. **Sidebar Integration in `sidebar.php`**: The standalone sidebar ([app/Views/components/sidebar.php](file:///c:/xampp/htdocs/sia/app/Views/components/sidebar.php)) has zero links to LMS Governance. It must be added to match `admin_navbar.php`.
2. **Dedicated Role / RBAC Tightening**: The system lacks an explicit `lms_admin` enum value in `users.role`. Furthermore, `LmsAdminController::enforceAdminAccess()` permits `scheduler` role access due to `requirePermission()` matching `sections.manage`.
3. **Course Content Cloner / Syllabus Template Exporter**: Faculty currently recreate modules and syllabus structure manually each semester. An administrative tool to copy published modules from an archived term to an active term is absent.
4. **Resolution Wizard for Ambiguous Conflicts**: Reconciliation flags duplicate shells and orphan courses, but provides no UI tool for administrators to inspect and merge duplicate shells that contain student work.
5. **System-Wide LMS Announcement Broadcaster**: LMS Admin cannot post platform-wide emergency notices (e.g. system maintenance, typhoon class suspensions) across all course shells simultaneously.

### Unnecessary Functionality (Must NOT Be Added to LMS Admin)
1. **Student Registration / Application Processing**: Already owned by Admissions (`admin/admissions/`).
2. **Fee Assessment & Payment Processing**: Already owned by Cashier / Finance (`admin/finance/`).
3. **Curriculum Design & Subject Creation**: Already owned by Registrar (`admin/registrar/`).
4. **Official Timetable Building & Room Assignment**: Already owned by Scheduler (`admin/scheduler/`).
5. **Final Official Grade Transcripts**: Owned by Registrar (`admin/registrar/`). LMS grades are formative classroom scores.

### Conflicting Functionality & Dangerous Duplication Risks
1. **Creating a Separate `lms_enrollments` Table**:
   - *Risk*: High. If students are inserted into an `lms_enrollments` table, dropping a subject or transferring a section in the Registrar module will cause data drift where the student remains enrolled in the LMS classroom.
   - *Prevention*: Enforce direct queries against `college_enrollments` and `shs_enrollments`. Never create a duplicate enrollment table.
2. **Direct Modifying of Section Codes or Subject Codes in LMS Admin**:
   - *Risk*: High. If LMS Admin edits `subject_code` or `section_code` in an LMS view, it creates conflicting sources of truth against `subjects` and `college_sections`.
   - *Prevention*: Section and subject fields must remain strictly read-only in LMS Admin views.
3. **Overwriting Irregular Student Custom Subjects**:
   - *Risk*: Medium. Automatic batch tools must never wipe custom subjects approved via `application_subject_requests`.
   - *Prevention*: Respect `ADR-011` isolation rules.

---

## 13. LMS ADMIN MUST-HAVE FEATURES

| Feature | Why LMS Admin Needs It | Data Affected | Current Implementation | Missing Implementation | Dependencies | Risk of Duplication |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Operational Dashboard** | Central observability of active shells, student access, unassigned shells, and term metrics | Read-only aggregation on `lms_courses`, `users`, `activity_logs` | Fully implemented in `LmsAdminController@dashboard` | None | `LmsAdminService` | Zero risk; pure read aggregation |
| **Course Catalog & Inspection** | Ability to monitor instructional progress, check rosters, and audit materials | Read-only queries on `lms_courses`, modules, assignments, roster | Fully implemented in `LmsAdminController@courses` and `courseDetail` | None | `LmsService` | Zero risk; read-only |
| **Faculty Reassignment with Timetable Sync** | Reassigning substitute instructors when faculty take leave or change load | `lms_courses.faculty_user_id`, `college_section_subjects.faculty_user_id` | Implemented in `LmsAdminController@reassignFaculty` | None | Scheduler timetable tables | Low risk; runs in single atomic transaction |
| **Deterministic Enrollment Sync & Reconcile** | Auto-provisions missing course shells and eliminates faculty drift against timetable truth | `lms_courses` | Fully implemented in `LmsAdminService::scanEnrollmentSync()` and `reconcileAllDeterministic()` | None | Timetable truth | Zero risk; strictly follows timetable |
| **LMS User Access Suspension** | Suspending LMS platform access for disciplinary holds without corrupting registrar enrollment | `users.lms_status` | Implemented in `LmsAdminController@updateUserStatus` | None | `users` table | Zero risk; decouples LMS access from `applications.status` |
| **Academic Term Course Archival** | Transitioning completed semesters to read-only status while preserving grades and student work | `lms_courses.status` | Implemented in `LmsAdminController@processArchiveTerm` | None | `college_sections` | Zero risk; non-destructive flag update |
| **LMS Administrative Audit Trail** | Immutable tracking of all administrative mutations and reconciliation actions | `activity_logs` | Implemented in `LmsAdminController@auditLogs` | None | `logActivity()` helper | Zero risk; append-only logging |
| **RBAC Security Guard Tightening** | Preventing unauthorized administrative roles (e.g. `scheduler`, `cashier`) from accessing LMS governance | `RoleMiddleware`, `LmsAdminController::enforceAdminAccess()` | Implemented but flawed (leaks access to `scheduler`) | Tighten `enforceAdminAccess()` to strictly require `superadmin`, `admin`, or explicit `lms.manage` permission | `functions.php`, `RoleMiddleware.php` | Zero risk; security defense |

---

## 14. LMS ADMIN SHOULD-HAVE FEATURES

| Feature | Why LMS Admin Needs It | Data Affected | Current Implementation | Missing Implementation | Dependencies | Risk of Duplication |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Global LMS Announcement Broadcaster** | Broadcasting emergency school closures, term dates, or maintenance notices across all shells | `lms_announcements` or `announcements` | Course-level announcements exist; admin broadcast missing | Multi-course batch announcement modal | `LmsAnnouncementService` | Low risk; purely instructional |
| **Duplicate Course Shell Merge Wizard** | Safely merging legacy duplicate shells where student submissions exist in both | `lms_submissions`, `lms_quiz_attempts`, `lms_courses` | Duplicates detected and flagged in sync scan; no UI merge tool | Interactive review tool allowing admin to migrate student artifacts before soft-deleting duplicate | `LmsAdminService` | Medium risk; requires atomic artifact migration |
| **Course Content Template Cloner** | Cloning standard course syllabus and modules from an archived shell to a new term shell | `lms_modules`, `lms_materials` | Absent | "Clone Modules" action in course inspection | File storage subsystem | Low risk; copies content without student submissions |
| **Standalone Sidebar Navigation Link** | Ensuring administrators navigating via `sidebar.php` can access LMS Governance | `app/Views/components/sidebar.php` | Linked in `admin_navbar.php`; omitted in `sidebar.php` | Add "LMS Governance" nav link in `sidebar.php` | `sidebar.php` | Zero risk; presentation only |

---

## 15. LMS ADMIN OPTIONAL FEATURES

| Feature | Why LMS Admin Needs It | Data Affected | Current Implementation | Missing Implementation | Dependencies | Risk of Duplication |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **LMS Storage Disk Space Analytics** | Tracking storage usage across course materials and student submission files | Read-only file system check | Absent | Disk usage widget on dashboard | Server OS filesystem | Zero risk |
| **Student Submission Export Tool** | Bulk downloading all student assignment files for institutional accreditation audits | `storage/uploads/lms/submissions/` | Individual file download exists via `DownloadController` | Zip archive generation stream | PHP `ZipArchive` extension | Zero risk |
| **LMS System Activity Heatmap** | Visualizing peak hourly student quiz taking and file submission activity | `lms_submissions`, `lms_quiz_attempts` | Raw counts exist | Chart.js visual timeline | Client-side charting | Zero risk |

---

## 16. LMS ADMIN MUST-NOT-HAVE FEATURES

| Feature | Why It Must NOT Be Added | Conflicting Authoritative Module | Risk of Duplication & Data Corruption |
| :--- | :--- | :--- | :--- |
| **Manual Student Enrollment Tool** | Concurring LMS course access outside of official registrar enrollment bypasses tuition cashiering and registrar verification | **Registrar & Admissions** | **CRITICAL**: Creates ghost students who attend classes without paying tuition or submitting valid admission credentials. |
| **Direct Section / Subject Editor** | Editing subject names, unit counts, or section codes in LMS Admin bypasses curriculum versioning and scheduling conflict checks | **Registrar & Scheduler** | **CRITICAL**: Breaks timetable consistency, corrupts academic transcripts, and causes data divergence across university records. |
| **Official Final Grade Submission / Transcript Generator** | LMS assignment scores are continuous formative learning assessments, not certified semester transcripts | **Registrar** | **HIGH**: Legally recognized grades must be certified and locked by the Registrar with official grading sheets. |
| **Separate `lms_enrollments` Table** | Maintaining a separate enrollment table duplicates `college_enrollments` and breaks synchronization during section transfers and subject drops | **Registrar** | **CRITICAL**: Direct violations of relational integrity and `ADR-011`. |
| **Tuition or Fee Modification** | Overriding course fees from LMS Admin disrupts financial assessment immutability | **Cashier / Finance** | **CRITICAL**: Violates statutory fee templates and `ADR-009` assessment item snapshots. |

---

## 17. PROPOSED LMS ADMIN NAVIGATION

The administrative navigation tree integrates seamlessly into the established institutional portal:

```text
LMS Administration
│
├── Dashboard (/admin/lms/dashboard)
│   ├── Operational KPIs (Courses, Faculty, Students, Tasks)
│   ├── Current Academic Term Summary
│   ├── Quick Action Hub (Catalog, Sync, Users, Generator, Archive, Logs)
│   └── Recent Administrative Audit Activity
│
├── Course Governance
│   ├── Course Catalog (/admin/lms/courses)
│   │   ├── Filter & Search Matrix (Level, Status, Faculty Binding)
│   │   └── Deep Course Inspection (/admin/lms/courses/{id})
│   │       ├── Timetable & Room Block (Read-only from Scheduler)
│   │       ├── Official Student Roster (Regular & Irregular indicators)
│   │       ├── Modules & Canonical Materials Browser
│   │       ├── Assignments & Submission Tracking
│   │       ├── Online Quizzes & Question Inspect
│   │       ├── Synchronized Faculty Reassignment (Action)
│   │       └── Course Lifecycle Status Toggle (Active / Archived)
│   └── Course Generator (/admin/lms/generator)
│       └── Idempotent Shell Provisioner for Unmapped Offerings
│
├── Synchronization & Reconciliation (/admin/lms/sync)
│   ├── Diagnostic Health Scan (Missing Shells, Faculty Drift, Duplicates, Orphans)
│   └── Deterministic Auto-Reconcile Action (/admin/lms/sync/reconcile)
│
├── User Access Governance (/admin/lms/users)
│   ├── User Platform Access Directory (Filter by Role, LMS Status)
│   └── Access Status Modification Modal (Active, Suspended, Inactive)
│
├── Term Lifecycle Management (/admin/lms/archive)
│   ├── Academic Term Overview
│   └── Bulk Term Archival Action (/admin/lms/archive/term)
│
└── Audit & Compliance (/admin/lms/audit_logs)
    └── Filterable Administrative Activity Log
```

### Detailed Menu Item Specification

| Menu Item | Purpose | Data It Manages | Underlying Source of Truth | What Admin Can Modify | What Admin Can Only View | What Admin Must NEVER Modify |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Dashboard** | Operational situational awareness | Operational metrics | Multiple modules | Nothing directly | All counts, active term, recent activity | Academic records, billing |
| **Course Catalog** | Master course shell index | `lms_courses` | `college_section_subjects` | Course status (`active`/`archived`) | Code, title, section, units, enrollment count | Subject catalog, section code |
| **Course Detail** | Deep inspection of classroom | `lms_courses` | Timetable & Enrollment | Faculty instructor (with sync) | Full roster, syllabus modules, assignments, quiz questions | Student enrollment status, official grades |
| **Course Generator** | Manual shell deployment | `lms_courses` | Section subjects | Provision new shell row | Unmapped offerings | Curriculum subjects, section definitions |
| **Sync & Reconcile** | Discrepancy diagnosis & fix | `lms_courses` | Timetable section-subjects | Run deterministic sync | Missing shells, faculty mismatches, duplicates, orphans | Timetable schedule, student course load |
| **User Access** | Disciplinary access management | `users.lms_status` | `users` table | `lms_status` (`active`/`suspended`) | User identity, student number, active courses | Application status, student number, email |
| **Term Archival** | Semester lifecycle rollover | `lms_courses.status` | `college_sections` | Bulk transition to `'archived'` | Active course counts by term | Submitted student work, historical grades |
| **Audit Logs** | Security & compliance audit | `activity_logs` | Append-only logs | Nothing (append-only) | Full log history, timestamps, actors, changes | Cannot edit or delete logs |

---

## 18. LMS WORKFLOWS

### Workflow 1: New Student Matriculation
```text
[Official Enrollment]
  1. Cashier marks payment_verified.
  2. Registrar clicks Finalize Enrollment (RegistrarController@finalizeEnrollment).
  3. EnrollmentService executes atomic transaction:
     - Assigns official Student Number (YYYY-XXXXXX).
     - Provisions institutional email (first.last@ttu.edu.ph).
     - Sets users.role = 'student', users.lms_status = 'active'.
     - Sets applications.status = 'enrolled'.
     - Inserts enrolled subjects into college_enrollments / shs_enrollments.
     - Calls LmsService::provisionCourseShell() for each subject.
[LMS Synchronization]
  4. Course shells are already guaranteed to exist or provisioned idempotently.
[LMS Access]
  5. Student receives welcome credentials email and logs in at /auth/lms_student_login.php.
  6. LmsAuthController validates applications.status = 'enrolled' and users.lms_status = 'active'.
[Course Availability]
  7. Student dashboard queries CollegeEnrollmentRepository::getActiveStudentCourses(userId).
  8. All enrolled section course cards render immediately with gradient covers and schedule times.
```

### Workflow 2: New Faculty Assignment
```text
[Scheduler Assignment]
  1. Scheduler opens Schedule Builder (/admin/scheduler/schedule_builder.php).
  2. Assigns faculty member (faculty_user_id) to section subject.
  3. SchedulerController::process saves timetable and runs automated LMS upsert:
     INSERT INTO lms_courses (...) ON DUPLICATE KEY UPDATE faculty_user_id = VALUES(faculty_user_id).
[LMS Synchronization]
  4. If scheduler didn't sync, LMS Admin visits /admin/lms/sync.
  5. Scan detects faculty mismatch or missing shell; admin clicks "Run Reconcile".
  6. LmsAdminService::reconcileAllDeterministic updates lms_courses.faculty_user_id.
[Faculty Course Access]
  7. Faculty logs in at /auth/lms_faculty_login.php.
  8. LmsService::getFacultyCourses(facultyUserId) returns the course shell immediately.
  9. Faculty begins uploading modules and assignments.
```

### Workflow 3: New Section Creation
```text
[Section Creation]
  1. Scheduler creates Section (e.g. BSIT 2-A) in college_sections.
  2. Scheduler attaches curriculum subjects into college_section_subjects.
[LMS Representation]
  3. When schedule is finalized, lms_courses rows are provisioned with unique (level, section_id, subject_id).
[Course Access]
  4. Course shell remains in 'active' status. If no instructor is assigned, faculty_user_id is NULL (TBA).
  5. Students assigned to this section receive access immediately upon enrollment finalization.
```

### Workflow 4: Academic Term Start
```text
[Academic Setup]
  1. System Administrator sets active_school_year and active_semester in system_settings.
  2. Scheduler finalizes all timetable matrices.
  3. LMS Admin runs /admin/lms/sync diagnostic to verify 100% healthy shells and 0 mismatches.
[Faculty Access]
  4. Faculty prepare modules, upload syllabi, and author assignments in 'draft' mode.
  5. Faculty publish Module 1 ('published').
[Student Access]
  6. Enrolled students log in and immediately download Module 1 materials.
```

### Workflow 5: Student Drops Subject or Changes Section
```text
[Subject Dropped]
  1. Registrar executes RegistrarController@dropSubject.
  2. EnrollmentService updates college_enrollments.status = 'dropped', dropped_at = NOW().
  3. Active LMS access revoked immediately: repository filters status = 'enrolled'.
  4. Student hidden from active class gradebook and roster.
  5. Historical student submissions and quiz attempts are permanently retained in database.
[Section Transfer]
  1. Registrar executes RegistrarController@transferSection to new section.
  2. EnrollmentService atomically updates applications.section_id AND college_enrollments.college_section_id.
  3. Old section LMS access revoked; new section LMS course cards appear dynamically.
  4. Prior section student submissions remain intact in database.
```

### Workflow 6: Academic Term End (Archival)
```text
[Term Concludes]
  1. Registrar finalizes semester grading.
  2. LMS Admin navigates to /admin/lms/archive.
  3. Selects academic level, academic year (e.g. 2026-2027), and semester (First).
  4. Clicks "Archive Academic Term".
  5. LmsAdminService::archiveTerm executes transaction: updates lms_courses.status = 'archived'.
[Result]
  - Courses transition to read-only historical archives.
  - Faculty cannot edit modules or post assignments.
  - Students cannot submit new files or take quizzes.
  - All past submissions, grades, feedback, attempts, and materials remain 100% intact.
```

### Workflow 7: Faculty Changes During the Semester
```text
[Instructor Reassignment]
  Option A (Scheduler changes timetable):
    1. Scheduler changes faculty_user_id in Schedule Builder.
    2. lms_courses.faculty_user_id is automatically updated.
  Option B (LMS Admin performs emergency substitute assignment):
    1. LMS Admin navigates to /admin/lms/courses/{id}.
    2. Selects new instructor from eligible faculty dropdown.
    3. Checks "Synchronize with authoritative timetable schedule".
    4. Clicks "Reassign Instructor".
    5. LmsAdminService::reassignFaculty atomically updates lms_courses AND college_section_subjects.
    6. Old instructor loses teaching access; new instructor gains full access.
    7. All course modules, materials, student submissions, and grades remain completely untouched.
```

---

## 19. EDGE CASES

| # | Edge Case Trigger | Expected System Behavior | Responsible Module | LMS Behavior | Admin Intervention Required? |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **1** | Student changes section mid-semester | Shift active course view to new section; preserve historical work from previous section | Registrar (`EnrollmentService::transferSection`) | Revokes old section shell cards; grants new section shell access dynamically | **NO** (Handled automatically by Registrar action) |
| **2** | Student drops subject | Soft-deactivate subject enrollment; retain submitted work for audit compliance | Registrar (`EnrollmentService::dropSubject`) | Enrollment row marked `'dropped'`; student disappears from active roster and gradebook | **NO** (Handled automatically by Registrar action) |
| **3** | Student adds subject late | Grant course access immediately without modifying previously enrolled subjects | Registrar (`EnrollmentService`) | Dynamically resolves new subject's `lms_courses` shell on next page load | **NO** |
| **4** | Faculty reassigned mid-term | Swap course ownership to new instructor; keep all modules, submissions, and grades intact | Scheduler / LMS Admin | `lms_courses.faculty_user_id` updated; new faculty sees entire course history | **NO** if done via Scheduler or LMS Admin reassignment |
| **5** | Section cancelled | Hide section courses from active portals; preserve historical data | Scheduler | Section status set to inactive; LMS courses can be archived | **YES** (LMS Admin archives course shell) |
| **6** | Subject replaced in curriculum | Current enrolled cohorts retain old subject; new incoming cohorts adopt new subject | Registrar (`ADR-005`) | Active `lms_courses` for existing cohorts continue unaffected | **NO** |
| **7** | Course archived at semester end | Freeze course shell to read-only reference state; preserve all submissions and scores | LMS Admin (`LmsAdminService::archiveTerm`) | Course marked `'archived'`; submission uploads and quiz attempts disabled | **YES** (Admin clicks Archive Term) |
| **8** | Academic year changes | New term begins; previous term shells remain archived; new term shells provisioned | System Admin / Scheduler | New timetable schedule provisions fresh course shells | **NO** (Automatic upon schedule creation) |
| **9** | Duplicate LMS course created | System flags duplicate section-subject shells without blindly deleting student data | LMS Admin (`LmsAdminService::scanEnrollmentSync`) | Reconciliation alerts administrator; leaves shells intact for manual inspection | **YES** (Admin inspects and consolidates) |
| **10** | Missing LMS course shell | Active section subject exists in timetable but no `lms_courses` row exists | LMS Admin (`LmsAdminService::reconcileAllDeterministic`) | Reconcile auto-provisions missing shell bound to timetable faculty | **YES** (Admin runs Reconcile or auto-cron) |
| **11** | LMS synchronization failure | Database connection or transaction fails during schedule upsert | Scheduler / LMS Admin | Transaction rolls back cleanly; diagnostic scan flags unmapped shell on next audit | **YES** (Admin reviews sync dashboard) |
| **12** | Faculty account exists, no assignment | Faculty member is active in `users`, but not scheduled to teach any subjects | Scheduler | Faculty dashboard displays clean empty state: *"No active teaching assignments"* | **NO** |
| **13** | Student account exists, no enrollment | User is registered in `users`, but application is not in `status = 'enrolled'` | Admissions / Registrar | Student login rejected by `LmsAuthController` with guidance alert | **NO** |
| **14** | Official enrollment exists, no LMS access | Student officially enrolled, but `users.lms_status = 'suspended'` | LMS Admin | Student authenticated into portal is intercepted with suspension notice | **YES** (Admin reviews and lifts suspension) |
| **15** | LMS access exists after enrollment removed | Student enrollment was cancelled by Registrar, but student retains active session | Registrar / Middleware | On next request, `CollegeEnrollmentRepository` checks live DB and returns 0 courses | **NO** (Live DB check is dynamic) |

---

## 20. SECURITY / PERMISSION ANALYSIS

### Authorization Enforcement & Route Guarding
1. **Middleware Pipeline**:
   Requests to `/admin/lms/*` traverse [SessionSecurityMiddleware](file:///c:/xampp/htdocs/sia/app/Middleware/SessionSecurityMiddleware.php), [CsrfMiddleware](file:///c:/xampp/htdocs/sia/app/Middleware/CsrfMiddleware.php), [AuthMiddleware](file:///c:/xampp/htdocs/sia/app/Middleware/AuthMiddleware.php), and [RoleMiddleware:admin](file:///c:/xampp/htdocs/sia/app/Middleware/RoleMiddleware.php).
2. **Student & Faculty Route Isolation**:
   - Students attempting to access `/admin/lms/*` are blocked with HTTP 403 / redirect by `RoleMiddleware`.
   - Faculty attempting to access `/admin/lms/*` are blocked with HTTP 403 / redirect by `RoleMiddleware`.
   - Students attempting to access `/lms/faculty/*` are blocked by `RoleMiddleware:faculty`.
   - Faculty attempting to access `/lms/student/*` are redirected to `/lms/faculty/dashboard.php`.
3. **IDOR & Course Scoping Defenses**:
   - In [LmsService::isStudentAuthorizedForCourse()](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php#L51), a student can only view materials, submit assignments, and take quizzes for course IDs where their live `application_id` has an active `college_enrollments` or `shs_enrollments` row.
   - In [LmsService::isFacultyAuthorizedForCourse()](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php#L305), instructors can only manage course IDs where `faculty_user_id` matches their authenticated session ID.
   - In [DownloadController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php), students can only download submissions where `student_id == $_SESSION['user_id']`.
4. **Privilege Escalation Vulnerability in `LmsAdminController::enforceAdminAccess()`**:
   - In [app/Controllers/Admin/LmsAdminController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php#L38-L44):
     ```php
     if (function_exists('requirePermission')) {
         requirePermission(['programs.manage', 'curriculum.manage', 'sections.manage', 'users.manage']);
     }
     ```
   - In [app/Helpers/functions.php](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php#L689-L693):
     ```php
     foreach ($checkPermissions as $perm) {
         if (in_array($perm, $userPermissions, true)) {
             return true;
         }
     }
     ```
   - *Vulnerability*: The check uses **OR** logic. Because `scheduler` possesses `sections.manage`, a Scheduler officer passing through `RoleMiddleware:admin` satisfies `enforceAdminAccess()` and gains unauthorized access to LMS Admin tools.
   - *Remediation*: Refactor `enforceAdminAccess()` to strictly require `superadmin`, `admin`, or an explicit `lms.manage` permission.

---

## 21. CONFLICTS AND ARCHITECTURAL RISKS

1. **Freeform String vs User ID in Timetable**:
   `college_section_subjects` contains both `faculty_user_id` (integer FK) and `instructor` (freeform VARCHAR). If a scheduler updates the text string without updating `faculty_user_id`, LMS course synchronization breaks.
2. **Polymorphic Section Foreign Keys**:
   `lms_courses.academic_section_id` references either `college_sections.id` or `shs_sections.id` depending on `academic_level`. Because MySQL cannot enforce polymorphic foreign keys, database-level cascading deletes cannot be configured directly on `academic_section_id`.
3. **Ambiguous Duplicate Resolution**:
   If a seed script or legacy migration inserted duplicate course shells for the same section-subject, both shells exist in `lms_courses`. `LmsAdminService::scanEnrollmentSync()` flags these, but no automated merger is safe because students may have submitted assignments to both shells.
4. **Discrepant Documented Lesson Layer**:
   Multiple Obsidian documentation notes refer to `lms_lessons` and 3-tier hierarchies. Attempting to implement code referencing `lms_lessons` will immediately cause database exceptions.

---

## 22. RECOMMENDED FUTURE IMPLEMENTATION PHASES

### Phase A: Security & Navigation Hardening (Immediate)
1. **Fix `enforceAdminAccess()`**: Restrict authorization strictly to `users.role IN ('superadmin', 'admin')` or explicit `hasPermission('lms.manage')`. Block `scheduler` from managing LMS administrative features.
2. **Synchronize Sidebar Navigation**: Add the "LMS Governance" link to [app/Views/components/sidebar.php](file:///c:/xampp/htdocs/sia/app/Views/components/sidebar.php) to ensure parity with `admin_navbar.php`.
3. **Document Retirement**: Mark legacy documents referencing `lms_lessons` as superseded.

### Phase B: Duplicate Shell Consolidation Wizard
1. Build an interactive administrative merge wizard in `/admin/lms/courses` to inspect duplicate shells, migrate student submissions and quiz attempts into the canonical shell, and delete the redundant shell.

### Phase C: Syllabus & Course Template Cloning
1. Build an administrative tool allowing faculty or admins to clone published module structures and assignment prompts from an archived term into newly provisioned shells for the new term.

### Phase D: Multi-Course Announcement Broadcaster
1. Enable LMS Admin to publish a single broadcast announcement that propagates across all active course shells in a given term or academic level.

---

## 23. FILES INSPECTED

### Core Controllers
- [app/Controllers/Admin/LmsAdminController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php) (Lines 1–478)
- [app/Controllers/Lms/StudentController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php)
- [app/Controllers/Lms/FacultyController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php)
- [app/Controllers/Lms/LmsAuthController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php)
- [app/Controllers/Lms/DownloadController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php)
- [app/Controllers/Admin/Registrar/RegistrarController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/RegistrarController.php) (Lines 1–717)
- [app/Controllers/Admin/Scheduler/SchedulerController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php) (Lines 1–787)
- [app/Controllers/Admin/System/SystemController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/SystemController.php)

### Domain Services
- [app/Services/LmsAdminService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php) (Lines 1–883)
- [app/Services/LmsService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) (Lines 1–1047)
- [app/Services/EnrollmentService.php](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php) (Lines 1–600)
- [app/Services/LmsGradebookService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php)
- [app/Services/LmsQuizService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsQuizService.php)
- [app/Services/LmsAttendanceService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAttendanceService.php)
- [app/Services/LmsAnnouncementService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAnnouncementService.php)
- [app/Services/LmsCalendarService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsCalendarService.php)

### Repositories & Routing
- [app/Repositories/CollegeEnrollmentRepository.php](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php) (Lines 1–175)
- [app/Repositories/ShsEnrollmentRepository.php](file:///c:/xampp/htdocs/sia/app/Repositories/ShsEnrollmentRepository.php) (Lines 1–173)
- [app/Routes/web.php](file:///c:/xampp/htdocs/sia/app/Routes/web.php) (Lines 1–322)
- [app/Middleware/RoleMiddleware.php](file:///c:/xampp/htdocs/sia/app/Middleware/RoleMiddleware.php) (Lines 1–134)
- [app/Helpers/functions.php](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php) (Lines 660–730)

### Database Schema & Documentation
- [database/schema.sql](file:///c:/xampp/htdocs/sia/database/schema.sql) (Lines 400–850, 1220–1340)
- `docs/obsidian/LMS_SYSTEM_OVERVIEW.md`
- `docs/obsidian/LMS_MASTER_DOCUMENTATION.md`
- `docs/obsidian/LMS_PHASE_5_ADMINISTRATION_AND_GOVERNANCE.md`
- `docs/obsidian/LMS_FINAL_ARCHITECTURE_AND_GOVERNANCE.md`
- `docs/obsidian/LMS_FEATURE_AUDIT.md`
- `docs/obsidian/LMS_ENROLLMENT_INTEGRATION.md`
- `docs/obsidian/LMS_DATABASE_MAP.md`
- `docs/obsidian/LMS_ROUTE_MAP.md`
- `docs/obsidian/14 - Architecture Decisions/ADR-011 Multi-Section LMS Subject Instance Isolation and Irregular Student Subject Preservation.md`
- `docs/obsidian/02 - Modules/Registrar.md`
- `docs/obsidian/02 - Modules/Scheduler.md`
- `docs/obsidian/02 - Modules/Admissions.md`
- `docs/obsidian/02 - Modules/System Administration.md`

### Test Suites
- [scripts/tests/test_phase5_verification.php](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase5_verification.php) (Lines 1–322)
- [scripts/tests/test_phase6_hardening_suite.php](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase6_hardening_suite.php) (Lines 1–460)

---

## 24. DOCUMENTATION CONFLICTS

| Obsolete Documentation Source | Documentation Claim | Actual Implementation Reality | Authoritative Verdict |
| :--- | :--- | :--- | :--- |
| `docs/obsidian/LMS_FEATURE_AUDIT.md` (Lines 59–65) | LMS Admin Dashboard, Course Catalog, LMS User Management, Enrollment Sync, and Archival are classified as **`MISSING`**. | Fully implemented in [LmsAdminController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php) and [LmsAdminService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php). | **Code is Authoritative**. Documentation is stale from prior phases. |
| `docs/obsidian/LMS_ROUTE_MAP.md` (Lines 97–103) | Only two administrative routes exist: `/admin/lms/generator` and `/admin/lms/generate`. | Thirteen administrative routes are registered in [web.php:141-161](file:///c:/xampp/htdocs/sia/app/Routes/web.php#L141-L161). | **Code is Authoritative**. Route map omitted Phase 5 endpoints. |
| `docs/obsidian/02 - Modules/LMS_Database_Architecture.md` | Schema contains `lms_lessons` as intermediate between modules and materials. | `lms_lessons` table **does not exist** in database or code. Schema is strictly 2-tier (`lms_modules` $\rightarrow$ `lms_materials`). | **Database Schema is Authoritative**. |
| `docs/obsidian/02 - Modules/LMS_Phase_2_Foundation.md` | Course progress is tracked via dedicated table `lms_student_progress`. | `lms_student_progress` **does not exist**. Student progress is computed dynamically. | **Database Schema is Authoritative**. |
| `docs/obsidian/LMS_DATABASE_MAP.md` (Line 346) | `lms_courses` documented with only `college_section_id`. | Schema has `academic_level` and `academic_section_id` supporting both College and SHS. | **Database Schema is Authoritative**. |
| `docs/obsidian/16 - Page Relationships/13 - LMS Faculty Portal Relationship Map.md` | Claims `/lms/faculty/*` routes are protected by `RoleMiddleware:faculty`. | In [web.php:239](file:///c:/xampp/htdocs/sia/app/Routes/web.php#L239), `RoleMiddleware:faculty` is indeed applied to faculty routes. | **Documentation & Code are in sync**. |

---

## 25. OPEN QUESTIONS / UNKNOWNS

1. **Role Taxonomy Expansion**: Should `users.role` enum be migrated to include `'lms_admin'` as a first-class citizen, or should LMS administrative control remain an attribute of `superadmin`/`admin` governed by JSON permissions in `users.permissions`?
2. **Synchronous vs Asynchronous Reconciliation at Scale**: The deterministic reconcile operation currently runs synchronously in an active PDO transaction. For institutional deployments exceeding 1,000 course shells, will execution exceed PHP script execution timeouts (`max_execution_time`)?
3. **Historical Grade Transmission**: Does TTU intend for final computed LMS gradebook marks to be pushed automatically back into official Registrar grade sheets (`student_grades` or equivalent), or will instructors continue manually keying official grades into the Registrar portal?

---

# IMPLEMENTATION READINESS

```text
================================================================================
                            IMPLEMENTATION READINESS:
                             READY WITH CONDITIONS
================================================================================
```

### Justification
The core LMS Administration and Governance architecture is already **extensively implemented, functional, and verified** by automated test suites ([scripts/tests/test_phase5_verification.php](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase5_verification.php) and [scripts/tests/test_phase6_hardening_suite.php](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase6_hardening_suite.php)). The domain boundaries are respected, data flows are unidirectional, and zero duplicate enrollment tables exist.

However, implementation of further extensions cannot proceed blindly without addressing specific security conditions and navigation alignments.

---

### BLOCKERS (Must be resolved before modifying or expanding LMS Admin)
1. **Permission Leakage in `enforceAdminAccess()`**:
   - *Problem*: In [app/Controllers/Admin/LmsAdminController.php:38-44](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php#L38-L44), falling back to `requirePermission(['programs.manage', 'curriculum.manage', 'sections.manage', 'users.manage'])` allows users with role `scheduler` (who possess `sections.manage`) to access all LMS administrative governance routes.
   - *Resolution Required*: Enforce that only `superadmin`, `admin`, or users with explicit `lms.manage` / `lms.admin` permissions can pass `enforceAdminAccess()`.

---

### QUESTIONS (Require institutional clarification)
1. Should `lms_admin` be added as an explicit role in `users.role` enum, or remain governed by `superadmin`/`admin` roles?
2. When Scheduler changes an instructor on the timetable, should LMS faculty reassignment occur strictly automatically, or should an administrative approval step be required?
3. Should a manual merge wizard be introduced for legacy duplicate course shells?

---

### SAFE TO IMPLEMENT (Can be implemented without changing ownership boundaries)
1. **Sidebar Navigation Alignment**: Add the "LMS Governance" link to [app/Views/components/sidebar.php](file:///c:/xampp/htdocs/sia/app/Views/components/sidebar.php).
2. **Course Syllabus / Module Template Cloner**: An administrative tool to copy modules and assignment prompts from an archived course shell to a new term's shell.
3. **Global LMS Announcement Broadcaster**: An administrative modal to broadcast notices across multiple course shells.
4. **Duplicate Shell Consolidation Wizard**: A UI workflow to merge duplicate course shells and migrate student submissions.
5. **Disk Space Usage Analytics**: Read-only tracking of uploaded materials and submissions storage volume.

---

### DO NOT IMPLEMENT (Would violate domain boundaries or corrupt data)
1. **DO NOT create an `lms_enrollments` table**: The LMS must continue to query `college_enrollments` and `shs_enrollments` dynamically.
2. **DO NOT allow LMS Admin to create or edit students, sections, or subjects**: These are authoritative responsibilities of Admissions, Scheduler, and Registrar.
3. **DO NOT allow LMS Admin to alter official application statuses (`applications.status`)**: Decouple LMS access via `users.lms_status`.
4. **DO NOT invent an `lms_lessons` table**: The system is designed and implemented around a clean 2-tier module-material structure.
5. **DO NOT push unverified LMS grades into official transcripts**: Final grades require official Registrar certification.
