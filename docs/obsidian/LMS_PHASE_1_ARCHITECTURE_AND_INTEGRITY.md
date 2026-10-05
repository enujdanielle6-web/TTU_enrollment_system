# TTU LMS PHASE 1 ARCHITECTURE & DATA INTEGRITY SPECIFICATION

> **Subsystem**: Learning Management System (LMS) & Enrollment Integration Engine  
> **Phase**: Phase 1 — LMS Architecture & Data Integrity  
> **Status**: Completed & Verified (14/14 Automated Verification Scenarios Passed)  
> **Implementation Date**: September 28, 2026  
> **Authoritative Root**: `c:\xampp\htdocs\sia`  

---

## 1. EXECUTIVE SUMMARY

Phase 1 established architectural stability, storage consistency, route authorization, repository purity, and strict enrollment gating across the TTU LMS subsystem.

Prior to Phase 1, critical structural issues compromised the LMS:
1. Faculty material uploads were escaping webroot into `c:\xampp\storage\lms_materials/`, while `DownloadController.php` attempted reads from `app/uploads/lms/`, causing 100% of material downloads to fail.
2. `DownloadController.php` checked `$_SESSION['role']`, which was unset because the authentication system uses `$_SESSION['user_role']`.
3. Unenrolled applicants whose application status was `approved` were permitted to log into the student LMS portal prior to tuition settlement or registrar finalization.
4. Student and faculty LMS routes lacked role-level route authorization guards (`RoleMiddleware`), leaving endpoints accessible to cross-role or unauthorized authenticated users.
5. Enrollment repositories (`CollegeEnrollmentRepository` and `ShsEnrollmentRepository`) mutated the database by executing `INSERT INTO lms_courses` inside read queries (`getActiveStudentCourses()`).
6. Course shells without an assigned instructor disappeared or failed because `lms_courses.faculty_user_id` was `NOT NULL` and read queries skipped unassigned rows.

All six structural defects have been resolved, verified, and locked with automated regression tests.

---

## 2. CANONICAL LMS STORAGE ARCHITECTURE

### 2.1 Storage Contract & Canonical Directories
All LMS uploaded assets are now consolidated under the canonical directory structure:

```text
c:\xampp\htdocs\sia\storage\uploads\lms/
├── materials/       # Faculty uploaded course materials (lecture notes, syllabi, readings)
└── submissions/     # Student assignment file submissions
```

### 2.2 Webroot Protection (`.htaccess`)
Direct HTTP access to the `storage/` directory from outside the application is prohibited via [c:/xampp/htdocs/sia/.htaccess](file:///c:/xampp/htdocs/sia/.htaccess):
```apache
RewriteRule ^(app|config|database|storage)/ - [F,L]
```
Any attempt to access `http://localhost/sia/storage/...` directly in the browser receives an immediate `HTTP 403 Forbidden`. All files must be accessed through authenticated streaming controller endpoints.

### 2.3 Faculty Upload Pipeline
In [app/Controllers/Lms/FacultyController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php), the `uploadMaterial()` method enforces:
* **Canonical Destination**: Resolves target directory via `dirname(__DIR__, 3) . '/storage/uploads/lms/materials/'`.
* **Safe Directory Creation**: Creates missing directories recursively with permissions `0775`.
* **Unique Randomized Naming**: Generates obfuscated filenames via `bin2hex(random_bytes(16)) . '_' . $sanitizedOriginalName` to eliminate file overwriting.
* **Extension Blacklist**: Rejects executable extensions (`php`, `phtml`, `phar`, `exe`, `bat`, `cmd`, `sh`, `py`, `js`, `vbs`, `html`, `htm`).
* **MIME Inspection**: Validates and stores `mime_type` using `finfo_file(FILEINFO_MIME_TYPE)`.
* **File Size Cap**: Enforces a 25 MB limit and persists `file_size` in bytes to `lms_materials`.

### 2.4 Secure File Streaming (`DownloadController.php`)
In [app/Controllers/Lms/DownloadController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php):
* **Path Traversal Defense**: Filters file identifiers using `basename()` and verifies that resolved paths reside within the project root.
* **Course Authorization Verification**:
  * If the requester is a student, calls `LmsService::isStudentAuthorizedForCourse($userId, $courseId)`.
  * If the requester is faculty, calls `LmsService::isFacultyAuthorizedForCourse($userId, $courseId)`.
  * If the user is neither or unauthorized, returns `HTTP 403 Forbidden`.
* **Backwards Compatibility**: Checks the canonical path (`storage/uploads/lms/materials/`), falling back cleanly to legacy paths (`app/uploads/lms/`, `app/uploads/lms/materials/`) if older files exist.
* **Stream Headers**: Sends correct `Content-Type`, `Content-Disposition`, `Content-Length`, `Cache-Control: private`, and streams data via `readfile()`.

---

## 3. LMS AUTHENTICATION & SESSION ARCHITECTURE

### 3.1 Session Contract Standardization
The authentication contract standardizes on the core session keys set during user authentication:
* `$_SESSION['logged_in']` (bool `true`)
* `$_SESSION['user_id']` (integer)
* `$_SESSION['user_role']` (string `'student'`, `'faculty'`, `'admin'`, `'superadmin'`, etc.)
* `$_SESSION['lms_role']` (string `'student'` or `'faculty'`)

In [DownloadController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php), authorization checks now read:
```php
$userRole = $_SESSION['user_role'] ?? $_SESSION['lms_role'] ?? '';
```
This eliminates the session mismatch where `$_SESSION['role']` was expected.

---

## 4. ENROLLMENT $\rightarrow$ LMS ACCESS ELIGIBILITY RULE

### 4.1 Strict Registrar Finalization Gating
The TTU Enrollment System is the authoritative source of truth. Under institutional business rules:
> **LMS access requires `applications.status = 'enrolled'`. An applicant whose application status is `'approved'` or `'payment_verified'` is NOT eligible for LMS course access.**

In [app/Controllers/Lms/LmsAuthController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php):
```sql
SELECT a.status, a.id as application_id, u.lms_status 
FROM applications a 
JOIN users u ON a.user_id = u.id 
WHERE a.user_id = :user_id 
ORDER BY a.id DESC 
LIMIT 1
```
Login processing verifies:
```php
if ($applicantCheck && $applicantCheck['status'] !== 'enrolled') {
    // Access Denied: Official enrollment finalization required
    $_SESSION['login_error'] = 'Access Denied: Your enrollment has not been finalized by the Registrar. LMS access is only granted to officially enrolled students.';
    $this->redirect('/sia/auth/lms_student_login.php');
    return;
}
```

### 4.2 Dynamic Subject Enrollment Resolution
No redundant `lms_enrollments` table exists. Student course eligibility is resolved dynamically via live database joins against official registrar tables:
* **College Students**: `college_enrollments` join on `applications.status = 'enrolled'`.
* **Senior High Students**: `shs_enrollments` join on `applications.status = 'enrolled'`.

---

## 5. LMS ROUTE ROLE AUTHORIZATION ARCHITECTURE

In [app/Routes/web.php](file:///c:/xampp/htdocs/sia/app/Routes/web.php), route groups are partitioned with dedicated middleware stacks:

### 5.1 Student Portal Routes
Protected by `SessionSecurityMiddleware`, `CsrfMiddleware`, `AuthMiddleware`, and `RoleMiddleware:student`:
* `/lms/student/dashboard.php`
* `/lms/student/course.php`
* `/lms/student/my_courses.php`
* `/lms/student/course/{course_id}/assignments`
* `/lms/student/course/{course_id}/assignments/{id}`
* `/lms/student/course/{course_id}/assignments/{id}/submit`
* `/lms/student/course/{course_id}/quizzes`
* `/lms/student/course/{course_id}/quizzes/{id}`
* `/lms/student/course/{course_id}/quizzes/{id}/start`
* `/lms/student/course/{course_id}/quizzes/{quiz_id}/attempt/{attempt_id}`
* `/lms/student/course/{course_id}/quizzes/{quiz_id}/attempt/{attempt_id}/submit`
* `/lms/student/course/{course_id}/quizzes/{quiz_id}/result/{attempt_id}`
* `/lms/student/course/{course_id}/gradebook`
* `/lms/student/course/{course_id}/attendance`
* `/lms/student/course/{course_id}/announcements`
* `/lms/student/calendar`
* `/lms/student/profile.php`

### 5.2 Faculty Portal Routes
Protected by `SessionSecurityMiddleware`, `CsrfMiddleware`, `AuthMiddleware`, and `RoleMiddleware:faculty`:
* `/lms/faculty/dashboard.php`
* `/lms/faculty/course.php`
* `/lms/faculty/module_create.php`
* `/lms/faculty/material_upload.php`
* `/lms/faculty/profile.php`
* `/lms/faculty/calendar`
* `/lms/faculty/course/{course_id}/assignments`
* `/lms/faculty/course/{course_id}/assignments/create`
* `/lms/faculty/course/{course_id}/assignments/{id}/edit`
* `/lms/faculty/course/{course_id}/assignments/{id}/submissions`
* `/lms/faculty/course/{course_id}/assignments/{assignment_id}/submissions/{id}/grade`
* `/lms/faculty/course/{course_id}/quizzes`
* `/lms/faculty/course/{course_id}/quizzes/create`
* `/lms/faculty/course/{course_id}/quizzes/{id}/questions`
* `/lms/faculty/course/{course_id}/quizzes/{id}/attempts`
* `/lms/faculty/course/{course_id}/gradebook`
* `/lms/faculty/course/{course_id}/attendance`
* `/lms/faculty/course/{course_id}/announcements`

### 5.3 Shared File Streaming Routes
Protected by `AuthMiddleware` with per-course authorization handled internally by `DownloadController`:
* `/lms/download/material/{id}`
* `/lms/download/submission/{id}`

### 5.4 Redirection on Unauthorized Access
[app/Middleware/RoleMiddleware.php](file:///c:/xampp/htdocs/sia/app/Middleware/RoleMiddleware.php) inspects role membership and redirects appropriately:
* Unauthorized student attempting to reach faculty routes $\rightarrow$ redirected to `/sia/lms/student/dashboard.php`.
* Unauthorized applicant attempting to reach student/faculty LMS $\rightarrow$ redirected to `/sia/applicant/dashboard.php`.
* Unauthorized system staff (e.g., cashier) $\rightarrow$ redirected to root login.

---

## 6. REPOSITORY PURITY & COURSE PROVISIONING

### 6.1 Read Repository Immutability
`CollegeEnrollmentRepository::getActiveStudentCourses()` and `ShsEnrollmentRepository::getActiveStudentCourses()` were refactored to eliminate all side-effects:
* **Zero Database Mutations**: Removed the JIT `INSERT INTO lms_courses` statements.
* **Deterministic Read Contracts**: Repository read queries now query existing `lms_courses` via `LEFT JOIN` without inserting shells.

### 6.2 Explicit Course Shell Provisioning Boundary
LMS course shell provisioning is now an explicit write operation encapsulated in [app/Services/LmsService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php):
```php
public function provisionCourseShell(
    string $academicLevel,
    int $sectionId,
    int $subjectId,
    ?int $facultyUserId = null,
    string $schoolYear = '2026-2027',
    string $semester = 'First'
): int
```
* **Upsert Logic**: Uses `ON DUPLICATE KEY UPDATE faculty_user_id = VALUES(faculty_user_id)` relying on the unique index `(academic_level, academic_section_id, subject_id)`.
* **Scheduler Integration**: In [app/Controllers/Admin/Scheduler/SchedulerController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php), when a timetable block is scheduled or updated, `LmsService::provisionCourseShell()` is executed synchronously.
* **Batch Provisioning Script**: An administrative CLI utility was deployed at [scripts/provision_lms_courses.php](file:///c:/xampp/htdocs/sia/scripts/provision_lms_courses.php) to ensure all active section-subjects have corresponding LMS shells.

---

## 7. COURSES WITHOUT ASSIGNED FACULTY (TBA INSTRUCTOR)

### 7.1 Schema Nullability
The MariaDB table `lms_courses` was updated:
```sql
ALTER TABLE lms_courses MODIFY faculty_user_id INT(10) UNSIGNED DEFAULT NULL;
```
The foreign key constraint `fk_lms_course_faculty` is preserved, allowing `NULL` values when no instructor has been assigned by the Dean or Academic Scheduler.

### 7.2 UI Rendering & Authorization
* When `faculty_user_id IS NULL`, `LmsService::getCourseDetails()` returns `NULL` instructor names.
* Student course cards, timetable summaries, and syllabus views display `'Instructor TBA'`.
* Read repositories no longer execute `continue;` on unassigned faculty, ensuring students see their full enrolled course catalog.
* `LmsService::isFacultyAuthorizedForCourse()` returns `false` if `faculty_user_id IS NULL`, preventing any faculty user from claiming unassigned course shells.

---

## 8. AUTOMATED VERIFICATION RESULTS (14/14 PASSED)

The automated verification suite in [scripts/tests/test_phase1_verification.php](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase1_verification.php) executed with 100% success:

| # | Verification Scenario | Tested Condition | Result |
| :--- | :--- | :--- | :---: |
| 1 | **Enrolled Student Login** | Student ID 11 (`2026-000001`) with `applications.status = 'enrolled'` is eligible for student LMS portal. | **PASS** |
| 2 | **Approved Applicant Gated** | Applicant with `applications.status = 'approved'` is rejected from LMS login. | **PASS** |
| 3 | **Student Route Guard** | `RoleMiddleware(['faculty'])` rejects student session from faculty routes. | **PASS** |
| 4 | **Faculty Route Guard** | `RoleMiddleware(['student'])` rejects faculty session from student routes. | **PASS** |
| 5 | **Unrelated User Gated** | Cashier role is rejected from both student and faculty LMS routes. | **PASS** |
| 6 | **Canonical Material Upload** | Upload path verified at `storage/uploads/lms/materials/test_verification_doc.pdf`. | **PASS** |
| 7 | **Authorized Student Download** | Student 11 enrolled in BSIT 1-A is authorized for Course 1 (`CC101`). | **PASS** |
| 8 | **Unauthorized User Download** | Non-enrolled user ID 999999 is denied download access to Course 1. | **PASS** |
| 9 | **Path Consistency** | Canonical path resolves inside project root `c:\xampp\htdocs\sia\storage\uploads\lms\materials`. | **PASS** |
| 10 | **Repository Purity** | Executing `getActiveStudentCourses()` produces 0 inserts into `lms_courses` (count remains constant). | **PASS** |
| 11 | **Course Shell Stability** | Existing course shells remain fully functional with subject codes and names. | **PASS** |
| 12 | **TBA Instructor Handling** | Course shell with `faculty_user_id = NULL` loads cleanly with instructor rendered as TBA. | **PASS** |
| 13 | **Section Isolation** | Student in BSIT 1-A has access to Course 1 (BSIT 1-A) but NOT Course 11 (BSIT 1-B). | **PASS** |
| 14 | **Faculty Isolation** | Faculty 3 assigned to Course 1 is authorized; Faculty 2 is rejected. | **PASS** |

---

## 9. CONCLUSION & READINESS FOR PHASE 2

Phase 1 objectives have been fully satisfied. All identified high-risk architectural defects in storage, authentication eligibility, route authorization, repository purity, and faculty assignment handling have been eliminated.

**Readiness**: Phase 1 is officially complete and certified. Phase 2 may proceed when requested.
