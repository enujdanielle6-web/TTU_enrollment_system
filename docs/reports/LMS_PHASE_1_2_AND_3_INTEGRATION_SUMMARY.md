# TTU LMS INTEGRATION & REBUILD: PHASES 1, 2, & 3 TECHNICAL HANDOFF SPECIFICATION

> **NOTICE**: This document has been updated and superseded by [`LMS_PHASE_1_2_3_AND_4_INTEGRATION_SUMMARY.md`](file:///c:/xampp/htdocs/sia/LMS_PHASE_1_2_3_AND_4_INTEGRATION_SUMMARY.md) following the completion and verification of Phase 4 (Faculty LMS Completion).
>
> **Subsystem**: Learning Management System (LMS) & Enrollment System Integration  
> **Repository Root**: `c:\xampp\htdocs\sia`  
> **Status**: Phase 1, Phase 2, & Phase 3 Fully Implemented, Verified, and Locked  
> **Verification Suites**:
> * Phase 1: [`scripts/tests/test_phase1_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase1_verification.php) — **14 / 14 Passed (100%)**
> * Phase 2: [`scripts/tests/test_phase2_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase2_verification.php) — **8 / 8 Passed (100%)**  
> * Phase 3: [`scripts/tests/test_phase3_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase3_verification.php) — **20 / 20 Passed (100%)**  
> * Phase 4: [`scripts/tests/test_phase4_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase4_verification.php) — **24 / 24 Passed (100%)**  
> **Next Target**: Phase 5 (LMS Administration & Governance) — *Do NOT start until directed.*

---

## 1. PURPOSE & AGENT CONTEXT FOR FUTURE SESSIONS

This document is the **authoritative single-source technical handoff** for the ongoing rebuild and stabilization of the TTU Learning Management System (LMS) and its relationship with the TTU Enrollment System.

If you are an agent resuming work in a new conversation:
1. **Enrollment is the Academic Source of Truth**: The LMS never owns student enrollment records or course catalogs. It is a downstream learning experience dynamically derived from official enrollment.
2. **Hybrid MVC Architecture**: We follow the project's Fat Controller / Lightweight Repository pattern using Vanilla PHP, raw PDO queries, prepared statements, and Bootstrap 5 presentation views. SQL queries meant for business logic remain in controllers or domain services (`EnrollmentService`, `LmsService`, `LmsGradebookService`, `LmsQuizService`).
3. **No Unplanned Mutations**: Read operations (`GET` requests, course listing queries) must NEVER mutate database state (e.g. creating courses on page load is strictly forbidden).
4. **Non-Destructive Academic History**: Student academic activity (submissions, quiz attempts, attendance) must never be hard-deleted when students drop subjects or transfer sections.
5. **Always Run Regression Suites Before & After Modifying Code**:
   * `php scripts/tests/test_phase1_verification.php` (14/14 PASS)
   * `php scripts/tests/test_phase2_verification.php` (8/8 PASS)
   * `php scripts/tests/test_phase3_verification.php` (20/20 PASS)

---

## 2. SYSTEM ARCHITECTURE & HIGH-LEVEL DOMAIN RELATIONSHIP

```text
┌────────────────────────────────────────────────────────────────────────┐
│                      ENROLLMENT SUBSYSTEM                              │
│                    (Academic Source of Truth)                          │
├────────────────────────────────────────────────────────────────────────┤
│  • Academic Hierarchy: College & SHS Curricula, Programs & Strands     │
│  • Sections: `college_sections`, `shs_sections`                        │
│  • Subject Offerings & Schedules: `college_section_subjects`           │
│  • Student Admissions & Applications: `applications`                   │
│  • Official Course Enrollment Roster: `college_enrollments`,           │
│    `shs_enrollments` (states: 'enrolled', 'dropped', 'withdrawn')      │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼ Unidirectional Derivation
┌────────────────────────────────────────────────────────────────────────┐
│                         LMS SUBSYSTEM                                  │
│                 (Derived Learning Experience)                          │
├────────────────────────────────────────────────────────────────────────┤
│  • Course Shells: `lms_courses` (tied to section + subject)            │
│  • Learning Content: `lms_modules`, `lms_materials`                    │
│  • Assessments: `lms_assignments`, `lms_quizzes`                       │
│  • Student Activity: `lms_submissions`, `lms_quiz_attempts`            │
│  • Student Access: Dynamically resolved from active enrollment rows    │
│  • Personal Gradebook: O(1) Student Retrieval without Class Calculation│
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. PHASE 1 SUMMARY — LMS ARCHITECTURE & DATA INTEGRITY
- **Canonical Storage**: Standardized under `storage/uploads/lms/materials/`.
- **Authentication Gate**: Enforced `applications.status = 'enrolled'`.
- **Role Isolation**: Enforced strict `RoleMiddleware` boundaries between student, faculty, and administrative users.
- **Repository Purity**: Kept controllers in charge of SQL logic, models as basic containers.
- **TBA Course Shells**: Allowed `faculty_user_id` to be `NULL`, displaying as `"Instructor TBA"`.

---

## 4. PHASE 2 SUMMARY — ENROLLMENT ↔ LMS LIFECYCLE INTEGRATION
- **Deterministic Provisioning**: `LmsService::provisionCourseShell()` provisions shells deterministically at schedule creation or enrollment finalization, never inside read queries.
- **Lifecycle Schema**: Added `status ENUM('enrolled', 'dropped', 'withdrawn')` and `dropped_at` to `college_enrollments` and `shs_enrollments`.
- **Atomic Section Transfers**: `EnrollmentService::transferSection()` updates `applications.section_id` and all active subject enrollment rows inside a PDO transaction.
- **Subject Drops & Withdrawals**: `EnrollmentService::dropSubject()` revokes active LMS course access while preserving historical attempts and submissions.
- **Faculty Reassignment**: Syncs instructor changes immediately to the course shell.

---

## 5. PHASE 3 SUMMARY — STUDENT LMS COMPLETION

### 5.1 Student LMS Access & Course Derivation
- Student LMS access is derived directly from active enrollment rows: `college_enrollments.status = 'enrolled'` or `shs_enrollments.status = 'enrolled'`.
- Approved-only applicants, dropped subjects, and withdrawn subjects are immediately excluded from active access.
- Irregular student enrollments resolve cleanly per assigned section.

### 5.2 Student Dashboard & Course View
- Dashboard displays course cards with subject code, title, section, schedule, and instructor.
- Unassigned instructors render cleanly as `"Instructor TBA"` without warnings or missing avatar crashes.
- Server-side validation via `LmsService::isStudentAuthorizedForCourse($lmsCourseId, $studentId)` protects every course route. Parameter tampering is rejected with 403 Forbidden.

### 5.3 Modules & Materials
- Modules and materials are bound strictly to authorized course shells.
- Downloads are channeled through `DownloadController`, checking course authorization, sanitizing paths (`basename`), and streaming binary data without exposing filesystem paths.

### 5.4 Assignments & Submissions
- Fixed duplicate key exception on resubmission: `LmsService::submitAssignment()` updated to use `ON DUPLICATE KEY UPDATE` and `submitted_at = CURRENT_TIMESTAMP`.
- Submissions stored in canonical storage: `storage/uploads/lms/submissions/`.
- Hardened upload validation: 25 MB file limit, extension blacklist (`php`, `exe`, `sh`, `bat`, `cmd`, etc.), randomized obfuscated filename (`sub_{aid}_{uid}_{hex}.ext`), and MIME inspection via `finfo_file()`.
- Immutability check prevents student tampering with graded work (`status === 'graded'`).
- CSRF token added to assignment submission form.

### 5.5 Quizzes & Attempt Engine
- Fixed `LmsQuizService::startAttempt()` bug: reordered checks so existing `in_progress` attempts are resumed *before* checking the `max_attempts` cap.
- Strict IDOR and ownership checks in `start()`, `attempt()`, `submit()`, and `result()`: verifies quiz belongs to course, quiz is `'published'`, and attempt belongs to session user.
- CSRF tokens added to quiz attempt forms.

### 5.6 Student Gradebook Performance Optimization
- **Problem**: `LmsGradebookService::getStudentGradebook()` previously ran `getClassGradebook()`, loading all enrolled students, all assignments, and calculating class-wide distributions just to show one student their grades.
- **Fix**: Implemented `getStudentPersonalGradebook($lmsCourseId, $studentId)` with $O(1)$ complexity. Executes 3 targeted queries (assessments, student's submissions, student's attempts). No other student's data is queried or exposed.

### 5.7 Bulletins & Attendance
- Bulletins are scoped strictly to the authorized course.
- Attendance controller normalized with lowercase status comparison (`strtolower($h['status'])`). Scoped exclusively to session student.

---

## 6. COMPLETE TEST MATRIX & REGRESSION STATUS

All three phases have comprehensive automated CLI verification suites:

### 6.1 Phase 1 Verification Suite (14 / 14 PASS)
* Command: `php scripts/tests/test_phase1_verification.php`
* Coverage: Auth gate, role isolation, material storage, read immutability, TBA course shells, multi-tenant section isolation.

### 6.2 Phase 2 Verification Suite (8 / 8 PASS)
* Command: `php scripts/tests/test_phase2_verification.php`
* Coverage: Normal enrollment, atomic section transfer, subject drop, faculty reassignment, TBA shells, irregular enrollment, duplicate prevention, transaction rollback.

### 6.3 Phase 3 Verification Suite (20 / 20 PASS)
* Command: `php scripts/tests/test_phase3_verification.php`
* Coverage:
  1. Enrolled student accesses LMS courses
  2. Approved-but-not-enrolled applicant blocked
  3. Dropped subject access revoked
  4. Withdrawn subject access revoked
  5. Section transfer updates course access
  6. Multi-level student access isolation (College vs SHS)
  7. Section isolation (Section A vs Section B)
  8. Student denied faculty actions
  9. Authorized student views materials
  10. Unauthorized student denied material download
  11. Student views own course assignments
  12. Student assignment submission & resubmission (idempotent, no duplicate key error)
  13. Cross-course assignment submission blocked
  14. Student accesses authorized quiz
  15. Cross-course quiz access blocked
  16. Modifying or submitting another student's quiz attempt blocked
  17. Personal gradebook displays only student's own grades
  18. Student cannot view other students' grades
  19. Student personal gradebook uses $O(1)$ student-specific retrieval
  20. Direct ID manipulation across all LMS resources rejected server-side

---

## 7. FILE MANIFEST: MODIFIED & CREATED FILES IN PHASES 1, 2, AND 3

### Core Infrastructure & Services
- [`app/Services/LmsService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php): Course provisioning, course authorization, assignment submission (`ON DUPLICATE KEY UPDATE`).
- [`app/Services/LmsQuizService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsQuizService.php): Quiz attempt tracking, resume before max_attempts check.
- [`app/Services/LmsGradebookService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php): $O(1)$ personal student gradebook retrieval.
- [`app/Services/EnrollmentService.php`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php): Atomic section transfer, subject drops.

### Student LMS Controllers
- [`app/Controllers/Lms/StudentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php): Dashboard & course listing (`status = 'enrolled'`).
- [`app/Controllers/Lms/StudentCourseController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentCourseController.php): Course view with server-side authorization.
- [`app/Controllers/Lms/StudentAssignmentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAssignmentController.php): Assignment listing, detail, secure upload, graded protection.
- [`app/Controllers/Lms/StudentQuizController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentQuizController.php): Quiz listing, attempt flow, ownership validation.
- [`app/Controllers/Lms/StudentGradebookController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentGradebookController.php): Personal gradebook index invoking `getStudentPersonalGradebook()`.
- [`app/Controllers/Lms/StudentAttendanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAttendanceController.php): Attendance status normalization.
- [`app/Controllers/Lms/DownloadController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php): Secure file downloads from canonical storage.

### Student LMS Views
- [`app/Views/lms/student/dashboard.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/dashboard.php): Course card UI with Instructor TBA state.
- [`app/Views/lms/student/assignments/show.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/assignments/show.php): Added CSRF token.
- [`app/Views/lms/student/quizzes/show.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/quizzes/show.php): Added CSRF token to start/resume forms.
- [`app/Views/lms/student/quizzes/attempt.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/quizzes/attempt.php): Added CSRF token to submission form.

### Automated Test Scripts
- [`scripts/tests/test_phase1_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase1_verification.php)
- [`scripts/tests/test_phase2_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase2_verification.php)
- [`scripts/tests/test_phase3_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase3_verification.php)

### Documentation Files
- [`docs/obsidian/LMS_PHASE_1_ARCHITECTURE_AND_INTEGRITY.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_PHASE_1_ARCHITECTURE_AND_INTEGRITY.md)
- [`docs/obsidian/LMS_PHASE_2_LIFECYCLE_INTEGRATION.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_PHASE_2_LIFECYCLE_INTEGRATION.md)
- [`docs/obsidian/LMS_PHASE_3_STUDENT_COMPLETION.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_PHASE_3_STUDENT_COMPLETION.md)
- [`docs/obsidian/LMS_MASTER_DOCUMENTATION.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/LMS_MASTER_DOCUMENTATION.md)
- [`LMS_PHASE_1_2_AND_3_INTEGRATION_SUMMARY.md`](file:///c:/xampp/htdocs/sia/LMS_PHASE_1_2_AND_3_INTEGRATION_SUMMARY.md)

---

## 8. TRANSITION TO PHASE 4 (FACULTY LMS WORKFLOW POLISH)

When directed to begin **Phase 4**:
1. **Focus**: Faculty LMS workflows (course authoring, module/material management, assignment creation & grading, quiz authoring & question pool management, gradebook grading grid, roster viewing).
2. **Prerequisites Fulfilled**: Student LMS is complete, verified, secure against IDOR, and isolated from faculty routes.
3. **DO NOT START PHASE 4** until explicitly requested by the user.
