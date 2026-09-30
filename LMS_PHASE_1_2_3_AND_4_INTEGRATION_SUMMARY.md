# TTU LMS INTEGRATION & REBUILD: PHASES 1, 2, 3, & 4 TECHNICAL HANDOFF SPECIFICATION

> **Subsystem**: Learning Management System (LMS) & Enrollment System Integration  
> **Repository Root**: `c:\xampp\htdocs\sia`  
> **Status**: Phase 1, Phase 2, Phase 3, & Phase 4 Fully Implemented, Verified, and Locked  
> **Verification Suites**:
> * Phase 1: [`scripts/test_phase1_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase1_verification.php) — **14 / 14 Passed (100%)**
> * Phase 2: [`scripts/test_phase2_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase2_verification.php) — **8 / 8 Passed (100%)**  
> * Phase 3: [`scripts/test_phase3_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase3_verification.php) — **20 / 20 Passed (100%)**  
> * Phase 4: [`scripts/test_phase4_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase4_verification.php) — **24 / 24 Passed (100%)**  
> * **Total Verification Score**: **66 / 66 Automated Tests Passing (Zero Regressions)**  
> **Next Target**: Phase 5 (LMS Administration & Governance) — *Do NOT start until directed.*

---

## 1. PURPOSE & AGENT CONTEXT FOR FUTURE SESSIONS

This document is the **authoritative single-source technical handoff** for the rebuild, stabilization, and security hardening of the TTU Learning Management System (LMS) and its bidirectional integration with the TTU Enrollment System.

If you are an agent resuming work in a future session:
1. **Enrollment is the Academic Source of Truth**: The LMS never owns student enrollment records or course catalogs. It is a downstream learning experience dynamically derived from official enrollment.
2. **Hybrid MVC Architecture**: We follow the project's Fat Controller / Lightweight Repository pattern using Vanilla PHP, raw PDO queries, prepared statements, and Bootstrap 5 presentation views. SQL queries meant for business logic remain in controllers or domain services (`EnrollmentService`, `LmsService`, `LmsGradebookService`, `LmsQuizService`).
3. **No Unplanned Mutations**: Read operations (`GET` requests, course listing queries) must NEVER mutate database state (e.g. creating courses on page load is strictly forbidden).
4. **Non-Destructive Academic History**: Student academic activity (submissions, quiz attempts, attendance) must never be hard-deleted when students drop subjects or transfer sections.
5. **Always Run Regression Suites Before & After Modifying Code**:
   * `php scripts/test_phase1_verification.php` (14/14 PASS)
   * `php scripts/test_phase2_verification.php` (8/8 PASS)
   * `php scripts/test_phase3_verification.php` (20/20 PASS)
   * `php scripts/test_phase4_verification.php` (24/24 PASS)

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
│  • Faculty Management: Roster, Modules, Materials, Grading, Quizzes    │
│  • Bulk-Prefetched Gradebook: 4 queries total (eliminated N+1 problem) │
│  • Dynamic Student Access: Derived from active enrollment rows         │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. SUMMARY OF COMPLETED PHASES

### Phase 1: LMS Architecture & Data Integrity (14/14 Tests Passed)
1. **Canonical Storage Contract**: All uploaded materials write to and read from `storage/uploads/lms/materials/`.
2. **Gated Student Authentication**: `LmsAuthController` restricts student logins strictly to `applications.status = 'enrolled'`.
3. **Route Guarding**: Role-based access control separates Student and Faculty portals using `RoleMiddleware`.
4. **Idempotent Course Reading**: Read queries never trigger hidden course shell creation.
5. **Section & Course Isolation**: Cross-section and cross-course leaks prevented.

### Phase 2: Enrollment ↔ LMS Lifecycle Integration (8/8 Tests Passed)
1. **Explicit LMS Provisioning**: Automated by `EnrollmentService` on section scheduling.
2. **Atomic Section Transfer**: Moving a student section updates `applications.section_id` and `college_enrollments.college_section_id` atomically within a PDO transaction.
3. **Subject Drop / Withdrawal**: Mark status `'dropped'`/`'withdrawn'` with `dropped_at` timestamp. Historical submissions/attempts preserved.
4. **Faculty Reassignment**: Updating `faculty_user_id` instantly migrates ownership while preserving student submissions, grades, and content.
5. **TBA Shell Handling**: NULL faculty courses load cleanly as TBA without unauthorized access.
6. **Irregular Students**: Irregular student registrations are accurately tracked and reflected in faculty course loads.

### Phase 3: Student LMS Completion (20/20 Tests Passed)
1. **Dynamic Subject Access**: Directly derived from active enrollment rows (`status = 'enrolled'`).
2. **Material Downloads**: Secure delivery via [`DownloadController`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php) verifying course enrollment.
3. **Assignment Submissions**: Multi-file upload, idempotent resubmission with `ON DUPLICATE KEY UPDATE`.
4. **Timed Quizzes**: In-progress timer resumption, attempt limits, question presentation, instant scoring.
5. **Personal Student Gradebook**: $O(1)$ isolated retrieval without exposing peers or calculating whole-class matrices.

### Phase 4: Faculty LMS Completion (24/24 Tests Passed)
1. **Faculty Access Control & RBAC**: Enforced via `RoleMiddleware:faculty` and server-side `isFacultyAuthorizedForCourse()`.
2. **Course Ownership & IDOR Defenses**: Module, material, assignment, submission, quiz, question, and grade operations strictly bound to authorized courses.
3. **Module & Material Management**: Complete create, edit, delete with disk file unlinking to avoid orphaned files.
4. **Assignment Grading Queue**: Instructor grading verifies assignment belongs to course, submission belongs to assignment, assigns numeric grade and feedback.
5. **Quiz Authoring & Results**: Complete authoring (questions, choices, points), deletion, and attempt results analysis.
6. **High-Performance Gradebook**: Eliminated the $N \times (\text{assignments} + \text{quizzes})$ N+1 query loop! Pre-fetches all submissions and quiz attempts in bulk (`IN (...)`), reducing queries to **4 total**.
7. **Roster Truth & Irregular Handling**: Correctly supports regular and irregular students (`applications.section_id = NULL`), filtering out dropped/withdrawn students.
8. **Calendar Routing Bug Fixed**: Contextual URLs route faculty directly to submission grading and quiz result endpoints instead of 403 student URLs.
9. **CSRF Protection**: All faculty action forms now contain CSRF tokens (`<?= getCsrfInput() ?>`).

---

## 4. CANONICAL CODE ARTIFACTS & DIRECTORY STRUCTURE

| Component | Path | Purpose |
| :--- | :--- | :--- |
| **LMS Domain Service** | [`app/Services/LmsService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) | Course details, faculty courses, modules, materials, roster, and course authorization. |
| **Quiz Service** | [`app/Services/LmsQuizService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsQuizService.php) | Quiz lifecycle, questions, choices, attempt starts, submissions, grading. |
| **Gradebook Service** | [`app/Services/LmsGradebookService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php) | High-performance bulk gradebook query & isolated student grade profiles. |
| **Calendar Service** | [`app/Services/LmsCalendarService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsCalendarService.php) | Monthly calendar plotting with role-specific event links. |
| **Faculty Controller** | [`app/Controllers/Lms/FacultyController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php) | Dashboard, course management, module authoring, material upload/delete, roster. |
| **Faculty Assignment Controller** | [`app/Controllers/Lms/FacultyAssignmentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAssignmentController.php) | Assignment authoring, updating, deleting, submission review, and grading. |
| **Faculty Quiz Controller** | [`app/Controllers/Lms/FacultyQuizController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyQuizController.php) | Quiz authoring, question management, choices, deletion, and results queue. |
| **Faculty Roster View** | [`app/Views/lms/faculty/roster/index.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/roster/index.php) | Live class roster with regular/irregular badges, student numbers, contact info. |
| **Test Suites** | [`scripts/`](file:///c:/xampp/htdocs/sia/scripts/) | Verification scripts for Phase 1, Phase 2, Phase 3, and Phase 4. |

---

## 5. PHASE 5 READINESS

The system is now completely ready for **Phase 5 — LMS Administration & Governance**:
* Course shells are provisioned and synchronized with Enrollment and Scheduling.
* Student LMS is fully isolated, functional, and verified.
* Faculty LMS is fully hardened, authorized server-side, and optimized for high-performance class rosters.
* **Stop condition respected**: Do not begin Phase 5 until directed by the user.
