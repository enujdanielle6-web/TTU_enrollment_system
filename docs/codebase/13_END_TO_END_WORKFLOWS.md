# 13. END-TO-END WORKFLOW TRACE & FAILURE MODE ANALYSIS

**Document Reference:** `docs/codebase/13_END_TO_END_WORKFLOWS.md`  
**Execution Phase:** Phase 13 — End-to-End Workflow Trace  
**Repository:** Triple T University (TTU) Enrollment System & LMS  
**Date:** September 13, 2026  
**Status:** FORENSICALLY VERIFIED AGAINST SOURCE CODE & CANONICAL SCHEMA (`database/schema.sql`)

---

## 1. Executive Summary

This document traces the **five critical academic workflows** across Triple T University's hybrid codebase, documenting the exact files invoked, database read/write queries, session mutations, and failure modes at every step of execution.

---

## 2. Workflow 1: The Complete Student Lifecycle

```mermaid
sequenceDiagram
    autonumber
    actor S as Student / Applicant
    participant Auth as AuthController
    participant Enroll as EnrollController
    participant Admin as Admissions / Clinic / Cashier
    participant Serv as EnrollmentService
    participant LMSAuth as LmsAuthController
    participant LMS as StudentController / Services
    participant DB as MariaDB (sia)

    S->>Auth: Register Account (POST /sia/auth/register.php)
    Auth->>S: 6-Digit Email OTP Dispatched
    S->>Auth: Verify OTP (POST /sia/auth/verify_email.php)
    Auth->>DB: INSERT INTO users (role='applicant', email_verified=1)
    
    S->>Enroll: Submit Enrollment (POST /sia/applicant/enroll.php)
    Enroll->>DB: INSERT INTO applications (status='pending', section_id)
    
    Admin->>DB: Verify Documents & Set status='approved'
    Admin->>DB: Clinic clears medical & Cashier logs tuition payment
    
    Admin->>Serv: Finalize Enrollment (AdmissionsController / EnrollmentService)
    Serv->>DB: UPDATE users SET student_number='2026-XXXXXX', ttu_email='...', password=HASH(SN)
    Serv->>DB: UPDATE applications SET status='enrolled'
    Serv->>S: Credentials Email Dispatched (Student No & Temp Password)
    
    S->>LMSAuth: Student LMS Login (POST /sia/auth/lms_student_login.php)
    LMSAuth->>DB: Verify student_number & password in users
    LMSAuth->>DB: SELECT COUNT(*) FROM applications WHERE status IN ('enrolled','approved')
    LMSAuth->>S: Set $_SESSION['user_role']='student', redirect to /lms/student/dashboard.php
    
    S->>LMS: View Course (GET /sia/lms/student/course.php?id=X)
    LMS->>DB: Query college_enrollments & resolve lms_courses
    S->>LMS: Submit Assignment (POST /course/{id}/assignments/{aid}/submit)
    LMS->>DB: INSERT INTO lms_submissions (status='SUBMITTED')
    S->>LMS: View Grades (GET /course/{id}/gradebook.php)
    LMS->>DB: Aggregate lms_submissions & lms_quiz_attempts dynamically
```

### Detailed Trace Table for Workflow 1:

| Step | Controller & File Path | Database Operations (SQL) | Session State Changes | Failure Mode / Vulnerability |
| :--- | :--- | :--- | :--- | :--- |
| **1. Registration** | `AuthController::register`<br>`app/Controllers/AuthController.php:162-240` | `SELECT id FROM users WHERE email = :email` | Populates `$_SESSION['pending_registration']` with input and hashed password. | User leaves before OTP; account is uncreated. |
| **2. OTP Verification** | `AuthController::verifyEmail`<br>`app/Controllers/AuthController.php:270-338` | `INSERT INTO users (first_name, last_name, email, password, role='applicant', is_active=1, email_verified=1)` | `$_SESSION['logged_in'] = true`<br>`$_SESSION['user_id'] = $id`<br>`$_SESSION['user_role'] = 'applicant'` | Email collision during interim; unsets pending session. |
| **3. Application** | `EnrollController::processForm`<br>`app/Controllers/EnrollController.php:89-351` | `INSERT INTO applications (user_id, reference_number, academic_level, grade_level, strand, section_id, status='pending')` | Sets `$_SESSION['enroll_old']` on validation failure. | Submitting duplicate application redirects away (`EnrollController:39`). |
| **4. Approval** | `AdmissionsController::process`<br>`app/Controllers/Admin/Admissions/AdmissionsController.php:517-720` | `UPDATE applications SET status = 'approved', section_id = :sec`<br>`INSERT INTO college_enrollments (application_id, subject_id, college_section_id)`<br>`AssessmentService::generateAssessment` | Sets `$_SESSION['admin_success']` | Section capacity overflow if schedulers modified limits concurrently. |
| **5. Finalize** | `EnrollmentService::finalizeEnrollment`<br>`app/Services/EnrollmentService.php:25-196` | `UPDATE users SET student_number = :sn, ttu_email = :mail, password = :hash, force_password_reset = 1`<br>`UPDATE applications SET status = 'enrolled'` | None (Admin session active). | **Password Overwrite:** Destroys applicant's original personal password without notice. |
| **6. LMS Login** | `LmsAuthController::login`<br>`app/Controllers/Lms/LmsAuthController.php:62-117` | `SELECT * FROM users WHERE student_number = :sid AND is_active = 1`<br>`SELECT COUNT(*) FROM applications WHERE status IN ('enrolled','approved')` | `$_SESSION['user_role'] = 'student'`<br>`$_SESSION['lms_role'] = 'student'`<br>`$_SESSION['lms_logged_in'] = true` | **Role Mutation:** Overwrites `$_SESSION['user_role']`, breaking Enrollment route guards. |
| **7. Course View** | `StudentController::course`<br>`app/Controllers/Lms/StudentController.php:29-100` | `SELECT * FROM college_enrollments ce JOIN applications a ...`<br>`SELECT * FROM lms_courses WHERE academic_section_id = :sec` | None. | **Fallback Faculty:** Auto-provisions course with lowest-ID faculty member if unmapped. |
| **8. Submission** | `StudentAssignmentController::submit`<br>`app/Controllers/Lms/StudentAssignmentController.php` | `INSERT INTO lms_submissions (assignment_id, student_id, file_path, status='SUBMITTED')` | None. | File size/MIME validation bypass if upload limits misconfigured. |
| **9. Grade View** | `StudentGradebookController::index`<br>`app/Controllers/Lms/StudentGradebookController.php` | `LmsGradebookService::getStudentGradebook` queries `lms_submissions` & `lms_quiz_attempts` | None. | Slow read performance as submissions and quiz attempts scale (no indexing on grades). |

---

## 2. Workflow 2: The Faculty Lifecycle

```mermaid
sequenceDiagram
    autonumber
    actor A as Administrator
    actor F as Faculty Member
    participant Sys as SystemController / DB Seeder
    participant Sched as SchedulerController
    participant LMSAdmin as LmsAdminController
    participant LMSAuth as LmsAuthController
    participant FC as FacultyController / Services
    participant DB as MariaDB (sia)

    Note over A,Sys: Web UI Creation BROKEN (role='faculty' blocked)
    A->>DB: Insert via seed.sql (role='faculty', student_number='FAC-2026-001')
    
    A->>Sched: Schedule Section (college_schedule.php)
    Sched->>DB: UPDATE college_section_subjects SET instructor='Alan Turing' (RAW STRING)
    Note over Sched,DB: Timetable does NOT update lms_courses!
    
    A->>LMSAdmin: Map LMS Course (/admin/lms/generator)
    LMSAdmin->>DB: INSERT INTO lms_courses (faculty_user_id=8, status='active')
    
    F->>LMSAuth: Faculty LMS Login (POST /sia/auth/lms_faculty_login.php)
    LMSAuth->>DB: SELECT * FROM users WHERE student_number='FAC-2026-001' AND role='faculty'
    LMSAuth->>F: Set $_SESSION['user_role']='faculty', redirect to /lms/faculty/dashboard.php
    
    F->>FC: Create Module & Upload Material (POST /course/{id}/modules)
    FC->>DB: INSERT INTO lms_modules & INSERT INTO lms_materials
    
    F->>FC: Create Assignment (POST /course/{id}/assignments)
    FC->>DB: INSERT INTO lms_assignments (max_score, due_date)
    
    F->>FC: Grade Student Submissions (POST /assignments/{aid}/grade)
    FC->>DB: UPDATE lms_submissions SET grade=95.0, status='GRADED', graded_by=8
    
    F->>FC: Track Attendance (POST /course/{id}/attendance)
    FC->>DB: INSERT INTO lms_attendance_sessions & INSERT INTO lms_attendance_records
```

### Detailed Trace Table for Workflow 2:

| Step | Controller & File Path | Database Operations (SQL) | Failure Mode / Vulnerability |
| :--- | :--- | :--- | :--- |
| **1. Faculty Creation** | Direct Database Seed (`database/seed.sql:44`) | `INSERT INTO users (first_name, last_name, email, student_number='FAC-2026-001', role='faculty', department='...')` | **UI Defect:** `SystemController.php:162` rejects `'faculty'`; administrators cannot create faculty via web UI. |
| **2. Course Assignment** | `SchedulerController::saveSchedule`<br>`SchedulerController.php:501` | `UPDATE college_section_subjects SET instructor = 'Alan Turing'` | **Loose String:** No foreign key; zero communication with `lms_courses`. |
| **3. LMS Mapping** | `LmsAdminController::generateLmsCourse`<br>`LmsAdminController.php:84` | `INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)` | Manual human bottleneck; if forgotten, auto-provisioning misassigns course. |
| **4. LMS Login** | `LmsAuthController::login`<br>`LmsAuthController.php:126` | `SELECT * FROM users WHERE student_number = :eid AND role = 'faculty' AND is_active = 1` | Overloaded identifier: requires Employee ID in `student_number` column. |
| **5. Module & Material**| `FacultyController::createModule`<br>`FacultyController.php:54-73` | `INSERT INTO lms_modules (lms_course_id, title, display_order)`<br>`INSERT INTO lms_materials (...)` | Authorized via `isFacultyAuthorizedForCourse`; no Dean/Chair override permitted. |
| **6. Grading** | `FacultyAssignmentController::grade`<br>`FacultyAssignmentController.php:134-149` | `UPDATE lms_submissions SET grade = :grade, feedback = :fb, status = 'GRADED', graded_by = :uid` | Grade stored purely in LMS; never synced to Registrar student transcript. |
| **7. Attendance** | `FacultyAttendanceController::save`<br>`FacultyAttendanceController.php` | `INSERT INTO lms_attendance_sessions`<br>`INSERT INTO lms_attendance_records ON DUPLICATE KEY UPDATE` | Student list resolved dynamically; dropped students vanish from attendance sheet. |

---

## 3. Workflow 3: Section Timetable Creation $\to$ Course Visibility

1. **Section Initialization (`SchedulerController::collegeSections`):**
   * Schedulers create section `BSCS 1-A` (`INSERT INTO college_sections`).
   * Queries `college_curriculum_subjects` matching year and semester.
   * Auto-imports subjects into `college_section_subjects` with placeholder values (`day = 'TBA'`, `start_time = '00:00:00'`, `instructor = NULL`).
2. **Timetable Scheduling (`SchedulerController::saveSchedule`):**
   * Schedulers assign `day = 'MWF'`, `start_time = '08:00:00'`, `end_time = '09:00:00'`, `room = 'Lab 101'`, `instructor = 'Dr. Grace Hopper'`.
   * Conflict checks execute against string `ss.instructor = 'Dr. Grace Hopper'`.
   * **The Disconnect:** Schedulers save the timetable. `lms_courses` is **not updated**.
3. **Student Enrollment (`AdmissionsController::process`):**
   * Admissions assigns students to section `BSCS 1-A` (`applications.section_id = :sec_id`).
   * Subjects inserted into `college_enrollments`.
4. **Course Visibility in LMS (`CollegeEnrollmentRepository::getActiveStudentCourses`):**
   * Student logs into LMS. Repository queries `lms_courses WHERE academic_section_id = :sec_id AND subject_id = :sub_id`.
   * If an admin did not manually map the course, repository auto-creates the course and assigns it to Alan Turing (`user_id = 8`), completely ignoring Dr. Grace Hopper.

---

## 4. Workflow 4: Student Drops or Withdraws

```mermaid
graph TD
    Withdraw["Student Drops / Withdraws in Registrar"] --> Action["Registrar Deletes Record from college_enrollments"]
    Action --> AccessCheck["Student Opens LMS Dashboard"]
    AccessCheck --> Repo["CollegeEnrollmentRepository::getActiveStudentCourses"]
    Repo --> Result1["Course Disappears from Student Dashboard"]
    
    Action --> Gradebook["Faculty Opens LMS Gradebook"]
    Gradebook --> RosterQuery["LmsGradebookService::getEnrolledStudents<br>(JOIN college_enrollments)"]
    RosterQuery --> Result2["CRITICAL FLAW: Dropped Student Disappears from Gradebook!"]
    Result2 --> OrphanedGrades["Previous Submissions & Quizzes become Orphaned in Database!"]
```

### Forensic Analysis of Withdrawal Impact:
* **In Enrollment Subsystem:** The Registrar removes the student from `college_enrollments`.
* **In LMS Course Access:** The student can no longer view the course or submit assignments (`isStudentAuthorizedForCourse` returns `false`).
* **The Gradebook Catastrophe:**
  * Because `LmsGradebookService` queries `college_enrollments` to construct the active student roster, **the dropped student immediately disappears from the faculty member's gradebook view**.
  * The student's historical assignment submissions (`lms_submissions`) and quiz attempts (`lms_quiz_attempts`) remain in the database, but the instructor can no longer view, export, or audit the work that the student completed prior to dropping.

---

## 5. Workflow 5: Faculty Member Leaves or is Replaced

```mermaid
graph TD
    Replace["Faculty Member Replaced in Scheduler"] --> Step1["Scheduler updates college_section_subjects.instructor = 'New Faculty'"]
    Step1 --> Gap["NO UPDATE EXECUTED ON lms_courses!"]
    Gap --> Consequence1["Old Faculty STILL OWNS the LMS Course!"]
    Gap --> Consequence2["New Faculty CANNOT ACCESS the LMS Course!"]
    
    Leave["Faculty Member Leaves University"] --> Delete["Admin Deletes Faculty from users Table"]
    Delete --> Trigger["MySQL ON DELETE CASCADE Triggers on fk_lms_course_faculty!"]
    Trigger --> Wipe1["lms_courses PERMANENTLY DELETED!"]
    Trigger --> Wipe2["lms_modules & lms_materials PERMANENTLY DELETED!"]
    Trigger --> Wipe3["lms_assignments & lms_submissions PERMANENTLY DELETED!"]
    Trigger --> Wipe4["lms_quizzes, questions & quiz attempts PERMANENTLY DELETED!"]
    Trigger --> Wipe5["lms_attendance_sessions & records PERMANENTLY DELETED!"]
```

### Forensic Analysis of Faculty Departure Impact:
1. **Replacement Inconsistency:** Updating an instructor in the Scheduler leaves `lms_courses.faculty_user_id` pointing to the former instructor. The former instructor retains administrative control over assignments and grades.
2. **The Nuclear Cascade Delete:**
   * Constraint: `CONSTRAINT fk_lms_course_faculty FOREIGN KEY (faculty_user_id) REFERENCES users (id) ON DELETE CASCADE` (`database/schema.sql:671`).
   * If a departed faculty member is deleted from `users`, the database engine automatically executes recursive cascading deletions across 8 relational tables, destroying all student work, submissions, quiz attempts, and attendance records associated with that instructor.

---

## 6. Summary & Next Phase Readiness

Phase 13 has traced the exact code paths and runtime mechanics of the five core institutional workflows:
* Uncovered the silent password destruction during enrollment finalization.
* Uncovered the complete erasure of dropped students from LMS gradebooks.
* Uncovered the persistent security breach during faculty reassignment.
* Uncovered the catastrophic cascade deletion hazard destroying academic history upon faculty removal.

We are fully prepared to proceed to **Phase 14: Architectural Gaps & Root Causes (Comprehensive synthesis of all gaps, root causes, and severity classifications across the entire codebase)**.
