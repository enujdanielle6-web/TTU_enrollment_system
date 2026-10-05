# TTU LMS PHASE 2 ENROLLMENT ↔ LMS LIFECYCLE INTEGRATION SPECIFICATION

> **Subsystem**: Enrollment System & Learning Management System (LMS) Integration  
> **Phase**: Phase 2 — Enrollment ↔ LMS Lifecycle Integration  
> **Status**: Completed & Verified (8/8 Automated Verification Matrix Scenarios Passed)  
> **Implementation Date**: September 28, 2026  
> **Authoritative Root**: `c:\xampp\htdocs\sia`  

---

## 1. EXECUTIVE SUMMARY & ARCHITECTURAL RELATIONSHIP

Phase 2 establishes the strict unidirectional relationship between the **Enrollment System** and the **Learning Management System (LMS)**:

```text
┌────────────────────────────────────────────────────────┐
│               ENROLLMENT SYSTEM                        │
│         (Academic Source of Truth)                     │
│  - Academic Level (College / SHS)                      │
│  - Student Admission & Application Status              │
│  - Section Assignment & Official Course Roster         │
│  - Subject Enrollment Status (enrolled, dropped, etc.) │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼ Unidirectional Derivation
┌────────────────────────────────────────────────────────┐
│            LEARNING MANAGEMENT SYSTEM                  │
│       (Learning Experience Derived from SIS)           │
│  - LMS Course Shells (`lms_courses`)                   │
│  - Modules, Materials, Assignments, Quizzes            │
│  - Active Student Access & Course Participation        │
│  - Gradebook & Attendance Rosters                      │
└────────────────────────────────────────────────────────┘
```

### Core Architecture Axioms:
1. **Enrollment is the Source of Truth**: LMS does not independently manage course rosters or enrollments. LMS access is dynamically resolved from active records in `college_enrollments` and `shs_enrollments`.
2. **No Read-Operation Mutations**: Course shells are provisioned deterministically during administrative lifecycle events (schedule setup, faculty assignment, enrollment finalization), never inside student or faculty read requests.
3. **Auditability & Non-Destructive Lifecycle**: Dropping or withdrawing from a subject revokes active LMS access and excludes the student from active gradebooks, but preserves historical quiz attempts, assignment submissions, and audit trails.
4. **Strict Atomicity**: All cross-table lifecycle mutations (section transfers, subject drops, enrollment finalizations) execute inside explicit PDO transactions with automatic rollback on error.

---

## 2. DATABASE SCHEMA MIGRATION

A database migration was executed to support enrollment lifecycle states without destroying relational rows or orphaning LMS historical records:

* **Migration Script**: [`database/migrations/add_enrollment_lifecycle_columns.php`](file:///c:/xampp/htdocs/sia/database/migrations/add_enrollment_lifecycle_columns.php)

### Schema Enhancements:
```sql
-- college_enrollments
ALTER TABLE college_enrollments 
    ADD COLUMN status ENUM('enrolled', 'dropped', 'withdrawn') NOT NULL DEFAULT 'enrolled' AFTER college_section_id,
    ADD COLUMN dropped_at TIMESTAMP NULL DEFAULT NULL AFTER status,
    ADD INDEX idx_ce_status (status),
    ADD INDEX idx_ce_sec_status (college_section_id, status);

-- shs_enrollments
ALTER TABLE shs_enrollments 
    ADD COLUMN status ENUM('enrolled', 'dropped', 'withdrawn') NOT NULL DEFAULT 'enrolled' AFTER shs_section_id,
    ADD COLUMN dropped_at TIMESTAMP NULL DEFAULT NULL AFTER status,
    ADD INDEX idx_se_status (status),
    ADD INDEX idx_se_sec_status (shs_section_id, status);
```

---

## 3. LMS COURSE SHELL PROVISIONING (TASK 1)

### 3.1 Provisioning Trigger Points
LMS course shells (`lms_courses`) are provisioned at official academic configuration milestones:
1. **Academic Scheduling / Section Subject Assignment**:
   When subjects and faculty are assigned to a section via [`app/Services/EnrollmentService.php`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php) (`assignSectionSubjects()`), course shells are provisioned with the designated `faculty_user_id`.
2. **Enrollment Finalization**:
   When an applicant's enrollment is finalized via [`app/Services/EnrollmentService.php`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php) (`finalizeEnrollment()`), all enrolled subjects for regular and irregular students trigger idempotent course shell provisioning.
3. **Faculty Reassignment / Unassignment**:
   When faculty are reassigned or removed (set to TBA), the course shell is updated via idempotent UPSERT.

### 3.2 Idempotent UPSERT Logic
In [`app/Services/LmsService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) (`provisionCourseShell()`):
```sql
INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
VALUES (:level, :sec_id, :sub_id, :fac_id, 'active')
ON DUPLICATE KEY UPDATE 
    faculty_user_id = VALUES(faculty_user_id),
    status = 'active',
    updated_at = NOW();
```
* **Uniqueness Constraint**: Enforced by composite key `(academic_level, academic_section_id, subject_id)`.
* **Idempotency**: Repeated provisioning requests return the existing `lms_courses.id` without creating duplicate course shells.
* **Faculty Synchronization**: Updating `faculty_user_id = VALUES(faculty_user_id)` ensures that assigning, transferring, or unassigning (NULL) an instructor immediately updates the course shell.

---

## 4. SECTION REASSIGNMENT (TASK 2)

### 4.1 Prior Defect
Previously, modifying `applications.section_id` in admissions or registrar workflows failed to synchronize `college_enrollments.college_section_id` (or `shs_enrollments.shs_section_id`). Consequently, a student reassigned from Section A to Section B remained enrolled in Section A's LMS course shells.

### 4.2 Atomic Section Transfer Implementation
Implemented [`EnrollmentService::transferSection()`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php):
```php
public static function transferSection(
    int $applicationId, 
    int $newSectionId, 
    ?int $performedByUserId = null, 
    ?PDO $pdo = null
): array
```

#### Transfer Flow:
1. **Validation**: Confirms application exists, status is `enrolled`, and target section matches the application's program/strand.
2. **Atomic Synchronization**: Inside a database transaction:
   * Updates `applications.section_id = :newSectionId`.
   * Queries existing enrolled subjects in `college_enrollments` or `shs_enrollments`.
   * Updates `college_enrollments.college_section_id = :newSectionId` for all active subjects.
   * Deterministically provisions LMS course shells for the new section's subjects.
3. **Course Access Shift**:
   * Old section course shells become inaccessible immediately because `isStudentAuthorizedForCourse()` evaluates current `college_section_id`.
   * New section course shells immediately become active for the student.
   * Historical submissions in the old section remain in the database for auditing and registrar review.

### 4.3 Controller Integration
* [`app/Controllers/Admin/Admissions/AdmissionsController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Admissions/AdmissionsController.php): For enrolled applicants, section updates execute via `EnrollmentService::transferSection()`.
* [`app/Controllers/Admin/Registrar/RegistrarController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/RegistrarController.php): Exposes `transferSection()` action with CSRF validation.

---

## 5. SUBJECT DROP / WITHDRAW PROPAGATION (TASK 3)

### 5.1 Prior Defect
Dropping or removing enrollment previously risked cascading hard deletes or leaving orphaned submissions and dangling foreign key relationships.

### 5.2 Non-Destructive State Transitions
Implemented [`EnrollmentService::dropSubject()`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php) and `withdrawSubject()`:
* **State Values**: `'enrolled'`, `'dropped'`, `'withdrawn'`.
* **Timestamping**: Records `dropped_at = NOW()` when status is transitioned from `'enrolled'`.
* **Zero Data Loss**: Assignment submissions (`lms_submissions`), quiz attempts (`lms_quiz_attempts`), and grade records remain intact.

### 5.3 Active Access Revocation & Gradebook Omission
* **Student LMS View**: Repositories filter active courses by `ce.status = 'enrolled' AND a.status = 'enrolled'`. Dropped courses disappear from the student's active dashboard.
* **Authorization Checks**: `isStudentAuthorizedForCourse()` checks `ce.status = 'enrolled'`. Any direct link access receives `HTTP 403 Forbidden`.
* **Gradebook Roster**: [`app/Services/LmsGradebookService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php) filters student rosters by `ce.status = 'enrolled' AND a.status = 'enrolled'`. Dropped students are immediately excluded from grading calculations and class averages.
* **Attendance Roster**: [`app/Controllers/Lms/FacultyAttendanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAttendanceController.php) omits dropped students from daily attendance rosters.

---

## 6. FACULTY ASSIGNMENT SYNCHRONIZATION (TASK 4)

### 6.1 Assignment Lifecycle
In `lms_courses`, the `faculty_user_id` column represents official instructor assignment:
* **Faculty Assigned**: Instructor has full access to create modules, upload materials, post announcements, publish assignments/quizzes, and enter grades.
* **Faculty Reassigned**: When reassigning from Instructor A to Instructor B:
  * Instructor B gains full access to the course space and existing course assets.
  * Instructor A immediately loses access (`isFacultyAuthorizedForCourse()` returns `false`).
* **Unassigned / TBA Shells**:
  * Setting `faculty_user_id = NULL` preserves the course shell and student enrollment.
  * The course displays instructor name as `"TBA"` on student dashboards.
  * Standard faculty access is rejected; administrative oversight users (`superadmin`, `admin`, or users with `lms.courses.manage`) retain view/edit access.

---

## 7. IRREGULAR STUDENT COURSE DERIVATION (TASK 5)

### 7.1 Prior Defect
Faculty course rosters and enrolled student counters previously attempted to count students via `applications.section_id = lms_courses.academic_section_id`. Irregular students often have `applications.section_id = NULL` or cross-enroll in subjects from multiple sections, causing them to disappear from faculty course counts.

### 7.2 Direct Enrollment Derivation
In [`app/Services/LmsService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) (`getFacultyCourses()`):
```sql
-- Accurate count directly derived from official subject enrollment
SELECT 
    lc.id AS lms_course_id,
    ...,
    (
        CASE 
            WHEN lc.academic_level = 'College' THEN (
                SELECT COUNT(DISTINCT ce.application_id)
                FROM college_enrollments ce
                JOIN applications a ON ce.application_id = a.id
                WHERE ce.college_section_id = lc.academic_section_id
                  AND ce.subject_id = lc.subject_id
                  AND ce.status = 'enrolled'
                  AND a.status = 'enrolled'
            )
            ELSE (
                SELECT COUNT(DISTINCT se.application_id)
                FROM shs_enrollments se
                JOIN applications a ON se.application_id = a.id
                WHERE se.shs_section_id = lc.academic_section_id
                  AND se.subject_id = lc.subject_id
                  AND se.status = 'enrolled'
                  AND a.status = 'enrolled'
            )
        END
    ) AS enrolled_count
FROM lms_courses lc ...
```
This guarantees:
1. Regular students enrolled in the section are counted.
2. Irregular students with `applications.section_id IS NULL` but enrolled in the section's subject are counted.
3. Dropped or withdrawn students are excluded from the count.

---

## 8. LMS COURSE ACCESS RESOLUTION (TASK 6)

### 8.1 Unified Resolution Pipeline
```text
Student Authenticates
         │
         ▼
Check Enrollment Status (`applications.status = 'enrolled'`)
         │
         ▼
Fetch Official Subjects (`college_enrollments` / `shs_enrollments`)
  Filter: `ce.status = 'enrolled'` AND `a.status = 'enrolled'`
         │
         ▼
Map to LMS Course Shells (`lms_courses`)
  Key: `(academic_level, academic_section_id, subject_id)`
         │
         ▼
Resolve Course Content (Modules, Materials, Assessments)
```

No student can access an LMS course shell unless there is an active, corresponding record in `college_enrollments` or `shs_enrollments`.

---

## 9. TRANSACTION BOUNDARIES & ATOMICITY (TASK 7)

Every multi-table academic operation is wrapped in a strict PDO transaction:
* **Section Transfer**: Encapsulates `applications` update, `college_enrollments` / `shs_enrollments` updates, and course shell provisioning.
* **Enrollment Finalization**: Encapsulates tuition verification, student number generation, user activation, section subject enrollment, and LMS provisioning.
* **Subject Dropping**: Encapsulates status transition, timestamp recording, and audit logging.

### Rollback Guarantee:
If any query within the transaction fails (e.g. invalid target section, foreign key error, database lock):
1. `PDO::rollBack()` is immediately invoked.
2. All modifications are undone; no partial state is persisted.
3. A structured error response `['success' => false, 'error' => $e->getMessage()]` is returned.

---

## 10. VERIFICATION TEST MATRIX RESULTS

All 8 scenarios specified in the Phase 2 prompt were executed and verified via [`scripts/tests/test_phase2_verification.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase2_verification.php):

| Scenario | Objective | Validation Method | Result |
| :--- | :--- | :--- | :--- |
| **A. Normal Enrollment** | Student enrolls → LMS course shell becomes available | Tested active enrolled student; verified course list and authorization check | **PASS** |
| **B. Section Transfer** | Section A → Section B | Transferred student from Section 1 (BSIT 1-A) to Section 3 (BSIT 1-B); verified Course 1 revoked, Course 11 granted, CE section updated | **PASS** |
| **C. Subject Drop** | Student drops subject | Marked Subject 1 as `'dropped'`; verified LMS access revoked, historical row preserved with `dropped_at`, and student excluded from gradebook | **PASS** |
| **D. Faculty Reassignment** | Faculty A → Faculty B | Reassigned Course 1 from Faculty 9 to Faculty 8; verified Faculty 8 gained access and Faculty 9 lost access | **PASS** |
| **E. Unassigned Faculty** | Course remains visible as TBA | Set `faculty_user_id = NULL`; verified student sees course with instructor "TBA", and non-admin faculty access is rejected | **PASS** |
| **F. Irregular Student** | Irregular student counted in course | Enrolled irregular student into Course 1 subject; verified student authorized and faculty course enrolled count incremented | **PASS** |
| **G. Duplicate Prevention** | Idempotent provisioning | Executed `provisionCourseShell()` 3 times consecutively; verified row count remained 1 and same ID was returned | **PASS** |
| **H. Transaction Failure** | Rollback on invalid operation | Attempted section transfer to invalid section ID 99999; verified failure caught, transaction rolled back, and enrollment state unmodified | **PASS** |

**Overall Verification Status**: **100% Passed (8 / 8)**.
