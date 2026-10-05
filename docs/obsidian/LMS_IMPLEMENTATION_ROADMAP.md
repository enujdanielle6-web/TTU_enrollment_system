# LMS Rebuild & Integration Roadmap

> **Strategic Directive**: Rebuild and improve the TTU LMS while safeguarding working Enrollment System features.  
> **Guiding Principle**: Do not destroy working Enrollment features; use adapters, services, clean database relationships, and strict role guards.  
> **Target Timeline**: 7 Systematic Engineering Phases  

---

## Phase 1 — Architecture & Data Integrity Baseline

### Objective
Resolve storage path escapes, eliminate JIT writes during HTTP read operations, and fix database schema and documentation discrepancies.

### Technical Tasks
1. **Fix File Storage Paths**:
   - Establish canonical storage path: `storage/uploads/lms/materials/` and `storage/uploads/lms/submissions/`.
   - Update `FacultyController::uploadMaterial` to store files inside the project storage directory.
   - Update `DownloadController` to read from the canonical storage path with verified MIME streaming.
2. **Eliminate JIT Writes in Read Repositories**:
   - Remove `INSERT INTO lms_courses` from `CollegeEnrollmentRepository::getActiveStudentCourses()` and `ShsEnrollmentRepository::getActiveStudentCourses()`.
   - Ensure repository queries remain pure idempotent reads.
3. **Handle TBA / Unassigned Faculty Gracefully**:
   - Refactor repository query so that courses lacking an assigned instructor render with `"Instructor TBA"` instead of being omitted via `continue`.
4. **Synchronize Schema Documentation**:
   - Update `Data Dictionary.md` and `Entity Relationship Architecture.md` to match active MariaDB columns (correcting `lms_materials`, `lms_submissions`, `lms_assignments`).

### Files Affected
* [app/Controllers/Lms/FacultyController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php)
* [app/Controllers/Lms/DownloadController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php)
* [app/Repositories/CollegeEnrollmentRepository.php](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php)
* [app/Repositories/ShsEnrollmentRepository.php](file:///c:/xampp/htdocs/sia/app/Repositories/ShsEnrollmentRepository.php)
* `docs/obsidian/04 - Database/Data Dictionary.md`

### Database Changes
* None (preserves existing 13 LMS tables and enrollment tables).

### Risks & Mitigations
* **Risk**: Existing uploaded files in `c:\xampp\storage\lms_materials/` might be orphaned.  
  * **Mitigation**: Add a migration script to copy legacy files into `storage/uploads/lms/materials/`.

### Expected Result
Uploaded materials are stored safely within the project hierarchy; file downloads stream successfully; repository read queries no longer execute database write operations.

---

## Phase 2 — Authentication & Authorization Gating

### Objective
Enforce strict institutional role boundaries, fix session key discrepancies, and prevent unenrolled applicants from accessing LMS environments.

### Technical Tasks
1. **Standardize Session Role Contract**:
   - Audit and fix all controllers to consistently reference `$_SESSION['user_role']`.
   - Fix `DownloadController` role evaluation so valid students and faculty are authorized.
2. **Strict Registrar Finalization Gating**:
   - Update `LmsAuthController::loginProcess` to require `applications.status = 'enrolled'`.
   - Replace JavaScript `alert()` with framework flash alerts (`$_SESSION['login_errors']`).
   - Guide approved but unenrolled applicants to cashier payment and registrar finalization.
3. **Route-Level Role Middleware Segregation**:
   - Split LMS routes in [app/Routes/web.php](file:///c:/xampp/htdocs/sia/app/Routes/web.php) into separate middleware groups:
     - Group 1: `/lms/student/*` protected by `RoleMiddleware:student,applicant` (with enrolled status check).
     - Group 2: `/lms/faculty/*` protected by `RoleMiddleware:faculty`.
   - Permit administrative override (`admin`, `superadmin`) for academic oversight.

### Files Affected
* [app/Routes/web.php](file:///c:/xampp/htdocs/sia/app/Routes/web.php)
* [app/Controllers/Lms/LmsAuthController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php)
* [app/Controllers/Lms/DownloadController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php)

### Database Changes
* None.

### Risks & Mitigations
* **Risk**: Students logging in through `/auth/login.php` might be routed away from LMS.  
  * **Mitigation**: Ensure `AuthController` preserves dual portal redirect handling for student role.

### Expected Result
Unenrolled applicants cannot enter the LMS; students cannot access faculty authoring routes; faculty cannot access student submission forms; download authorizations evaluate cleanly.

---

## Phase 3 — Student LMS Experience Optimization

### Objective
Optimize student gradebook calculation performance, complete assignment submission workflows, and verify responsive presentation.

### Technical Tasks
1. **Scoped Gradebook Query Optimization**:
   - Add `LmsGradebookService::getStudentPersonalGradebook($lmsCourseId, $studentId)` querying only the authenticated student's submissions and quiz attempts in a single $O(1)$ query.
   - Refactor `StudentGradebookController::index` to use the scoped method instead of calculating the entire course grid.
2. **Assignment Deliverable Enhancements**:
   - Display submitted file size, upload timestamp, and faculty feedback notes cleanly.
   - Support submission retraction / resubmission before due date deadline.
3. **Assessment Engine Hardening**:
   - Ensure `StudentQuizController` enforces strict time limits with automatic submission upon timer expiration.
   - Prevent resubmissions after maximum attempt threshold is reached.

### Files Affected
* [app/Services/LmsGradebookService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php)
* [app/Controllers/Lms/StudentGradebookController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentGradebookController.php)
* [app/Controllers/Lms/StudentAssignmentController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAssignmentController.php)
* [app/Views/lms/student/gradebook/index.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/gradebook/index.php)

### Database Changes
* None.

### Risks & Mitigations
* **Risk**: Calculation divergence between faculty gradebook and student gradebook.  
  * **Mitigation**: Share identical grading formula helpers across `LmsGradebookService`.

### Expected Result
Student gradebook loads instantaneously without executing class-wide queries; assignment and quiz workflows operate reliably.

---

## Phase 4 — Faculty LMS Workflow Polish

### Objective
Fix faculty calendar routes, correct enrolled student count metrics for irregular cohorts, and refine submission grading queues.

### Technical Tasks
1. **Faculty Calendar URL Route Correction**:
   - Fix [LmsCalendarService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsCalendarService.php) lines 81 and 122 to return faculty authoring URLs (`/sia/lms/faculty/course/...`) when requested by faculty.
2. **Accurate Enrolled Student Counts (Irregular Student Inclusion)**:
   - Refactor `LmsService::getFacultyCourses` subquery to count enrolled students from `college_enrollments` / `shs_enrollments` where `applications.status = 'enrolled'`.
   - Ensure irregular students enrolled in specific section subjects are properly reflected on faculty course cards.
3. **Gradebook Eligibility Filter**:
   - Update `LmsGradebookService::getEnrolledStudents` to require `applications.status = 'enrolled'`, preventing unapproved or withdrawn students from appearing on the active grading sheet.

### Files Affected
* [app/Services/LmsCalendarService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsCalendarService.php)
* [app/Services/LmsService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php)
* [app/Services/LmsGradebookService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php)
* [app/Views/lms/faculty/dashboard.php](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/dashboard.php)

### Database Changes
* None.

### Expected Result
Faculty calendar events navigate directly to grading/authoring interfaces; faculty dashboard course cards report 100% accurate class sizes including irregular students; gradebook sheets exclude un-enrolled applicants.

---

## Phase 5 — LMS Administrative Governance Module

### Objective
Build comprehensive administrative oversight and course lifecycle management tools.

### Technical Tasks
1. **Administrative Course Master Catalog**:
   - Build `LmsAdminController@index` displaying all generated LMS courses with section, subject, academic level, and assigned faculty.
   - Add search and filter by department, semester, and instructor.
2. **Instructor Reassignment**:
   - Build `LmsAdminController@reassignFaculty` allowing administrators to transfer a course shell to a substitute faculty member when an instructor takes leave.
   - Synchronize reassignment to `college_section_subjects.faculty_user_id`.
3. **Course Shell Archival & Rollover**:
   - Support archiving courses at semester completion (`status = 'archived'`).
   - Support cloning syllabus modules from previous semesters into new course shells.

### Files Affected
* [app/Controllers/Admin/LmsAdminController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php)
* [app/Routes/web.php](file:///c:/xampp/htdocs/sia/app/Routes/web.php)
* New View: `app/Views/admin/system/lms_courses.php`
* New View: `app/Views/admin/system/lms_course_detail.php`

### Database Changes
* None (uses existing `lms_courses` columns).

### Expected Result
LMS administrators have complete visibility and control over course shells, instructor assignments, and term-end archival.

---

## Phase 6 — Enrollment ↔ LMS Synchronization Layer

### Objective
Establish deterministic, automated synchronization between Enrollment events and LMS course provisioning.

### Technical Tasks
1. **Automated Shell Generation on Schedule Finalization**:
   - Ensure [SchedulerController::process](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php#L770) automatically upserts `lms_courses` whenever section schedules are saved.
2. **Section Reassignment Propagation**:
   - When an administrator transfers a student to a new section, update both `applications.section_id` and `college_enrollments.college_section_id` in an atomic database transaction.
3. **Subject Drop State Handling**:
   - Introduce formal drop state handling in `college_enrollments` / `shs_enrollments`.
   - Update LMS repository queries to ensure dropped subjects immediately disallow new task submissions while preserving existing academic records.

### Files Affected
* [app/Controllers/Admin/Scheduler/SchedulerController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php)
* [app/Services/EnrollmentService.php](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php)
* [app/Repositories/CollegeEnrollmentRepository.php](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php)
* [app/Repositories/ShsEnrollmentRepository.php](file:///c:/xampp/htdocs/sia/app/Repositories/ShsEnrollmentRepository.php)

### Database Changes
* Optional schema enhancement: Add `status ENUM('enrolled', 'dropped', 'withdrawn') NOT NULL DEFAULT 'enrolled'` to `college_enrollments` and `shs_enrollments`.

### Expected Result
Course shells are always synchronized with timetable schedules; student section transfers propagate to LMS instantly; dropped subjects are handled cleanly without data corruption.

---

## Phase 7 — Comprehensive End-to-End Verification

### Objective
Implement automated regression suites validating the full lifecycle from application registration through LMS grading.

### Technical Tasks
1. **Automated Test Matrix Suite**:
   - Create `scripts/tests/test_lms_integration_full.php` executing automated assertions for Scenarios A through H:
     - Scenario A: New student enrollment $\rightarrow$ LMS availability.
     - Scenario B: Subject enrollment $\rightarrow$ LMS course authorization.
     - Scenario C: Multi-section course instance isolation.
     - Scenario D: Faculty assignment & class roster visibility.
     - Scenario E: Subject drop & access revocation.
     - Scenario F: Section transfer & course shell rebinding.
     - Scenario G: Unenrolled student access rejection.
     - Scenario H: Unauthorized faculty access rejection.
2. **Audit & Log Verification**:
   - Validate that sensitive LMS operations record entries in `activity_logs`.

### Files Affected
* New Script: `scripts/tests/test_lms_integration_full.php`
* `docs/obsidian/13 - Reports/LMS_INTEGRATION_VERIFICATION_REPORT.md`

### Expected Result
100% passing test suite providing mathematical certainty that the LMS and Enrollment systems operate in harmony without regression.
