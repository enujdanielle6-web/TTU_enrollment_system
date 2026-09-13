# 04. USERS COLUMN DEPENDENCY GRAPH & TRACE REPORT

**Document Reference:** `docs/codebase/04_USERS_DEPENDENCY_GRAPH.md`  
**Execution Phase:** Phase 4 — Users Column Dependency Tracing  
**Target Table:** `users` (20 Columns)  
**Database:** `sia` (MariaDB 10.4+)  
**Date:** September 13, 2026  
**Status:** FORENSICALLY VERIFIED AGAINST ACTIVE SOURCE CODE

---

## 1. Executive Summary & Graph Overview

This document maps the complete dependency graph for all columns in the `users` table, tracing direct and indirect usage through:
1. **Controller -> Service -> Repository -> SQL Query**
2. **Route -> Middleware Pipeline -> Controller Action**
3. **Login -> Session Contract -> Authorization Engine -> Presentation View**

The analysis confirms that the `users` table is heavily coupled to both Enrollment and LMS modules, but in structurally different ways:
- **Enrollment** depends on `users` through foreign key relationships (`applications.user_id`, `student_assessments.user_id`, `payment_records.user_id`).
- **LMS** depends on `users` via:
  1. Student and Faculty authentication queries on `student_number` and `role`.
  2. Course ownership foreign keys (`lms_courses.faculty_user_id`).
  3. Student assessment tracking foreign keys (`lms_submissions.student_id`, `lms_quiz_attempts.student_id`, `lms_attendance_records.student_id`).
  4. Repository read-query fallback writes that query `users WHERE role = 'faculty'`.

---

## 2. Dependency Traces by Core Column

### 2.1 `users.id`
```text
[HTTP Request / Session]
  │
  ├──► Session State: $_SESSION['user_id'] & $_SESSION['lms_user_id']
  │
  ├──► Enrollment Dependency Chain:
  │      Route: /sia/applicant/dashboard.php
  │      └── Middleware: AuthMiddleware, RoleMiddleware:applicant
  │            └── ApplicantController::dashboard()
  │                  ├── App\Models\Application::findByUserId($_SESSION['user_id'])
  │                  │     └── SQL: SELECT * FROM applications WHERE user_id = :uid
  │                  ├── App\Models\HealthRecord::findByUserId($_SESSION['user_id'])
  │                  └── App\Services\EnrollmentService::finalizeEnrollment()
  │                        └── SQL: UPDATE users SET student_number = :sn, ttu_email = :email WHERE id = :id
  │
  └──► LMS Dependency Chain:
         Route: /sia/lms/student/dashboard.php
         └── Middleware: SessionSecurityMiddleware, CsrfMiddleware, AuthMiddleware
               └── StudentController::dashboard()
                     └── App\Services\LmsService::getStudentCourses($_SESSION['user_id'])
                           ├── App\Services\LmsService::getStudentRepository($userId)
                           │     └── SQL: SELECT academic_level FROM applications WHERE user_id = :uid AND status = 'enrolled'
                           └── App\Repositories\CollegeEnrollmentRepository::getActiveStudentCourses($userId)
                                 └── SQL: SELECT ce.id ... FROM college_enrollments ce
                                          JOIN applications a ON ce.application_id = a.id
                                          WHERE a.user_id = :uid
```
- **Files Involved:**
  - Read: `AuthController.php`, `LmsAuthController.php`, `ApplicantController.php`, `AdmissionsController.php`, `FinanceController.php`, `LmsService.php`, `LmsQuizService.php`, `LmsGradebookService.php`, `CollegeEnrollmentRepository.php`, `ShsEnrollmentRepository.php`.
  - Write: Generated on `INSERT` in `AuthController::processRegister()`, `SystemController::processUser()`.
- **Reason for Dependency:** Universal identity key for all institutional relationships.
- **Is Dependency Required:** Yes (Mandatory primary key).
- **Shared Status:** `SHARED / MULTI-DOMAIN`.

---

### 2.2 `users.role`
```text
[HTTP Request: Login / Route Dispatch]
  │
  ├──► Authentication & Session Initialization:
  │      POST /sia/auth/login.php -> AuthController::processLogin()
  │        ├── SQL: SELECT * FROM users WHERE email = :email LIMIT 1
  │        └── Writes: $_SESSION['user_role'] = $user['role']
  │
  ├──► Dedicated LMS Authentication:
  │      POST /sia/auth/lms_login.php -> LmsAuthController::login()
  │        ├── If student: Validates application enrolled status
  │        │     └── Writes: $_SESSION['user_role'] = 'student', $_SESSION['lms_role'] = 'student'
  │        └── If faculty:
  │              ├── SQL: SELECT * FROM users WHERE student_number = :eid AND role = 'faculty' AND is_active = 1
  │              └── Writes: $_SESSION['user_role'] = 'faculty', $_SESSION['lms_role'] = 'faculty'
  │
  ├──► Route Guarding (Middleware):
  │      App\Middleware\RoleMiddleware::handle()
  │        ├── Reads: $_SESSION['user_role']
  │        ├── Evaluates: $adminRoles = ['superadmin','admin','admissions','scholarship','cashier','clinic','scheduler']
  │        └── Redirects unauthorized users based on role value
  │
  ├──► Enrollment Authorization & Processing:
  │      App\Services\EnrollmentService::finalizeEnrollment()
  │        └── SQL: UPDATE users SET role = 'student' WHERE id = :id
  │
  ├──► LMS Course Generation (Admin):
  │      GET /sia/admin/lms/generator -> LmsAdminController::courseGenerator()
  │        └── SQL: SELECT id, first_name, last_name, email FROM users WHERE role = 'faculty' AND is_active = 1
  │
  └──► LMS Repository Read Fallback (Hazard):
         CollegeEnrollmentRepository::getActiveStudentCourses() / ShsEnrollmentRepository
           └── SQL: SELECT id FROM users WHERE role = 'faculty' ORDER BY id ASC LIMIT 1
```
- **Files Involved:**
  - Read: `AuthController.php`, `LmsAuthController.php`, `RoleMiddleware.php`, `LmsService.php`, `LmsAdminController.php`, `CollegeEnrollmentRepository.php`, `ShsEnrollmentRepository.php`, `SystemController.php`, `DownloadController.php`.
  - Write: `AuthController.php` (sets `'applicant'`), `EnrollmentService.php` (promotes to `'student'`), `SystemController.php` (updates role).
- **Reason for Dependency:** Governs portal access, RBAC routing, faculty identity lookup, and repository course provisioning.
- **Is Dependency Required:** Yes in current architecture; represents the primary coupling bottleneck between Enrollment and LMS.
- **Shared Status:** `SHARED / MULTI-DOMAIN`.

---

### 2.3 `users.student_number`
```text
[Enrollment Finalization]
  │
  ├──► Generation & Storage:
  │      RegistrarController::finalize() -> EnrollmentService::finalizeEnrollment()
  │        ├── App\Services\StudentNumberService::generate(int $year, PDO $pdo)
  │        │     ├── SQL: SELECT current_value FROM student_number_sequences WHERE sequence_year = :year FOR UPDATE
  │        │     └── Returns: e.g. "2026-000001"
  │        └── SQL: UPDATE users SET student_number = :sn WHERE id = :id
  │
  ├──► Enrollment Queries & Presentation:
  │      ├── FinanceController::receipt() -> SQL: SELECT u.student_number ... FROM users u
  │      ├── RegistrarController::students() -> Displayed as primary student identifier
  │      └── ScholarshipController::scholars() -> SQL: SELECT u.student_number ... FROM users u
  │
  └──► LMS Authentication & Course Operations:
         ├── Student Login: POST /sia/auth/lms_login.php
         │     └── SQL: SELECT * FROM users WHERE student_number = :sid AND is_active = 1
         ├── Faculty Login: POST /sia/auth/lms_login.php
         │     └── SQL: SELECT * FROM users WHERE student_number = :eid AND role = 'faculty' AND is_active = 1
         ├── Gradebook Display: LmsGradebookService::getEnrolledStudents()
         │     └── SQL: SELECT u.id, u.student_number, u.first_name, u.last_name FROM users u ...
         └── Attendance Roll Call: FacultyAttendanceController::create()
               └── SQL: SELECT u.id, u.student_number, u.first_name, u.last_name FROM users u ...
```
- **Files Involved:**
  - Read: `LmsAuthController.php`, `StudentNumberService.php`, `EnrollmentService.php`, `RegistrarController.php`, `FinanceController.php`, `ScholarshipController.php`, `LmsGradebookService.php`, `LmsAttendanceService.php`, `FacultyAttendanceController.php`, `AuthController.php`.
  - Write: `EnrollmentService.php` (line 83), `AdmissionsController.php` (line 727), `SystemController.php`.
- **Reason for Dependency:** Acts as the official public ID for students and employee ID for faculty. The LMS relies on it as the login username.
- **Is Dependency Required:** Yes.
- **Shared Status:** `SHARED / MULTI-DOMAIN`.

---

### 2.4 `users.first_name` & `users.last_name`
```text
[Registration / Profile Update]
  │
  ├──► Storage:
  │      AuthController::processRegister() / SystemController::processUser()
  │        └── SQL: INSERT INTO users (first_name, last_name, ...) VALUES (...)
  │
  ├──► Session Caching:
  │      AuthController::processLogin() / LmsAuthController::login()
  │        ├── $_SESSION['user_first_name'] = $user['first_name']
  │        ├── $_SESSION['user_last_name'] = $user['last_name']
  │        ├── $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name']
  │        └── $_SESSION['lms_name'] = $user['first_name'] . ' ' . $user['last_name']
  │
  ├──► Enrollment Usage:
  │      ├── Admissions review queue headers
  │      ├── Official tuition receipt printouts (FinanceController::receipt)
  │      └── Registrar student directory listings
  │
  └──► LMS Usage:
         ├── Course Instructor display: LmsService::getFacultyCourses()
         │     └── SQL: SELECT ... u.first_name, u.last_name FROM lms_courses lc JOIN users u ON lc.faculty_user_id = u.id
         ├── Student Gradebook Grid: LmsGradebookService::getEnrolledStudents()
         │     └── SQL: SELECT u.id, u.first_name, u.last_name ... ORDER BY u.last_name ASC, u.first_name ASC
         ├── Assignment Submissions: FacultyAssignmentController::submissions()
         │     └── SQL: SELECT ... u.first_name, u.last_name FROM lms_submissions s JOIN users u ON s.student_id = u.id
         └── Attendance Sessions: LmsAttendanceService::getSessionRecords()
               └── SQL: SELECT ... u.first_name, u.last_name FROM lms_attendance_records ar JOIN users u ON ar.student_id = u.id
```
- **Files Involved:**
  - Read: All controllers, services, and views rendering user names.
  - Write: `AuthController.php`, `SystemController.php`, `ApplicantController.php`.
- **Reason for Dependency:** Human identification across all reports, screens, receipts, and rosters.
- **Is Dependency Required:** Yes.
- **Shared Status:** `SHARED / MULTI-DOMAIN`.

---

### 2.5 `users.email` & `users.ttu_email`
```text
users.email (Personal)
  ├──► AuthController::processLogin() -> Primary login credential lookup
  ├──► AuthController::processRegister() -> Registration & OTP recipient
  ├──► AuthController::forgotPassword() -> Password reset link destination
  └──► LmsService::getFacultyCourses() -> Faculty contact displayed to students

users.ttu_email (Institutional)
  ├──► Generated in EnrollmentService::finalizeEnrollment()
  │      └── Algorithm: strtolower(firstName.lastName) . '@ttu.edu.ph'
  │            └── Duplicate check: SELECT COUNT(*) FROM users WHERE ttu_email = :email
  ├──► Displayed in student_academic_records_view
  ├──► Displayed on student portal profile (StudentController::profile)
  └──► Dispatched to student in welcome email via PHPMailer
```
- **Files Involved:**
  - `email`: Read/written in `AuthController.php`, `SystemController.php`, `LmsService.php`.
  - `ttu_email`: Written in `EnrollmentService.php`, `AdmissionsController.php`; read in `StudentController.php`, `RegistrarController.php`.
- **Reason for Dependency:** `email` handles outside authentication and notifications; `ttu_email` provides official student identity.
- **Is Dependency Required:** Yes.
- **Shared Status:** `email` is `SHARED / MULTI-DOMAIN`; `ttu_email` is `ENROLLMENT`.

---

### 2.6 `users.password`
```text
[Credential Ingestion]
  │
  ├──► Registration: AuthController::processRegister()
  │      └── password_hash($_POST['password'], PASSWORD_DEFAULT)
  │
  ├──► Enrollment Finalization: EnrollmentService::finalizeEnrollment()
  │      └── Default password set to student_number: password_hash($studentNumber, PASSWORD_DEFAULT)
  │
  ├──► Verification (Main Portal): AuthController::processLogin()
  │      └── password_verify($password, $user['password'])
  │
  └──► Verification (LMS Portal): LmsAuthController::login()
         └── password_verify($password, $user['password'])
```
- **Files Involved:**
  - Read: `AuthController.php`, `LmsAuthController.php`.
  - Write: `AuthController.php`, `EnrollmentService.php`, `SystemController.php`, `ApplicantController.php`.
- **Reason for Dependency:** Secure authentication secret.
- **Is Dependency Required:** Yes.
- **Shared Status:** `AUTHENTICATION`.

---

### 2.7 `users.is_active`
```text
[Administrative Toggle]
  │
  ├──► SystemController::processUser('toggle_status')
  │      └── SQL: UPDATE users SET is_active = :status WHERE id = :id
  │
  ├──► Main Authentication Guard: AuthController::processLogin()
  │      └── if ((int)$user['is_active'] !== 1) -> Rejects login with account deactivated message
  │
  ├──► LMS Student Login Guard: LmsAuthController::login()
  │      └── SQL: SELECT * FROM users WHERE student_number = :sid AND is_active = 1
  │
  ├──► LMS Faculty Login Guard: LmsAuthController::login()
  │      └── SQL: SELECT * FROM users WHERE student_number = :eid AND role = 'faculty' AND is_active = 1
  │
  └──► LMS Course Generator Dropdown: LmsAdminController::courseGenerator()
         └── SQL: SELECT id, first_name, last_name, email FROM users WHERE role = 'faculty' AND is_active = 1
```
- **Files Involved:**
  - Read: `AuthController.php`, `LmsAuthController.php`, `SystemController.php`, `LmsAdminController.php`.
  - Write: `SystemController.php`.
- **Reason for Dependency:** Global kill-switch / suspension flag for institutional accounts.
- **Is Dependency Required:** Yes.
- **Shared Status:** `AUTHENTICATION`.

---

### 2.8 `users.permissions`
```text
[Superadmin Configuration]
  │
  ├──► SystemController::processUser()
  │      └── Encodes array to JSON text: json_encode($_POST['permissions'])
  │            └── SQL: UPDATE users SET permissions = :perms WHERE id = :id
  │
  ├──► Session Ingestion: AuthController::processLogin()
  │      └── $_SESSION['user_permissions'] = json_decode($user['permissions'], true) ?? []
  │
  └──► Runtime Enforcement: app/Helpers/functions.php
         ├── hasPermission('fees.manage')
         └── requirePermission(['applications.review', 'documents.verify'])
```
- **Files Involved:**
  - Read: `AuthController.php`, `app/Helpers/functions.php`, `SystemController.php`.
  - Write: `SystemController.php`.
- **Reason for Dependency:** Granular access control for administrative staff.
- **Is Dependency Required:** Yes for Enrollment RBAC.
- **Shared Status:** `SHARED / MULTI-DOMAIN`.

---

### 2.9 `users.department`
```text
[Staff Assignment]
  ├──► SystemController::processUser() -> Writes department string
  ├──► AuthController::processLogin() -> Stores $_SESSION['user_department']
  ├──► LmsAuthController::login() -> Stores $_SESSION['user_department']
  └──► Displayed on admin navbar and faculty LMS profile
```
- **Files Involved:** `AuthController.php`, `LmsAuthController.php`, `SystemController.php`, `FacultyController.php`.
- **Shared Status:** `SHARED`.

---

### 2.10 `users.force_password_reset`
```text
[Credential Lifecycle]
  ├──► EnrollmentService::finalizeEnrollment() -> Sets force_password_reset = 1
  ├──► AuthController::processLogin() ->
  │      if (!empty($user['force_password_reset'])) {
  │          $_SESSION['force_password_reset_required'] = true;
  │          $response->redirect('/sia/applicant/profile.php');
  │      }
  └──► ApplicantController::updatePassword() -> Toggles force_password_reset = 0
```
- **Files Involved:** `EnrollmentService.php`, `AuthController.php`, `ApplicantController.php`.
- **Shared Status:** `AUTHENTICATION`.

---

## 3. The `faculty_user_id` Foreign Key Dependency Graph

`faculty_user_id` is the primary foreign key linking LMS courses to the `users` table (`lms_courses.faculty_user_id -> users.id`).

```text
lms_courses.faculty_user_id
  │
  ├──► LmsAdminController::generateLmsCourse()
  │      └── Validates faculty_user_id > 0
  │            └── SQL: INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
  │                     VALUES (:lvl, :sec, :sub, :fac, 'active')
  │
  ├──► LmsService::getFacultyCourses(int $facultyUserId)
  │      └── SQL: SELECT lc.id as lms_course_id, s.subject_code, s.subject_name ...
  │               FROM lms_courses lc
  │               JOIN subjects s ON lc.subject_id = s.id
  │               WHERE lc.faculty_user_id = :fid AND lc.status = 'active'
  │
  ├──► LmsService::isFacultyAuthorizedForCourse(int $userId, int $lmsCourseId)
  │      └── SQL: SELECT id FROM lms_courses WHERE id = :lcid AND faculty_user_id = :uid AND status = 'active'
  │            └── Guard: Used by FacultyController, FacultyAssignmentController, FacultyQuizController, DownloadController
  │
  ├──► CollegeEnrollmentRepository / ShsEnrollmentRepository (JIT Fallback Hazard):
  │      └── When student opens dashboard and course is unmapped:
  │            ├── SQL: SELECT id FROM users WHERE role = 'faculty' ORDER BY id ASC LIMIT 1 (fallback: 18)
  │            └── SQL: INSERT INTO lms_courses (..., faculty_user_id, ...) VALUES (..., :fac_id, ...)
  │
  └──► MariaDB Constraint Rule:
         CONSTRAINT fk_lms_course_faculty FOREIGN KEY (faculty_user_id) REFERENCES users (id) ON DELETE CASCADE
         (Hazard: Purging a faculty user wipes out all their LMS courses, modules, assignments, and grades)
```

---

## 4. Indirect Dependency Chains & Hidden Couplings

### 4.1 The Enrollment-State to LMS-Access Chain
A student cannot access the LMS until their Enrollment record reaches `'enrolled'`:
```text
POST /sia/auth/lms_login.php (Student ID + Password)
  │
  ▼
LmsAuthController::login()
  │ (Extracts $student_number, fetches user record)
  ▼
SQL: SELECT COUNT(*) FROM applications WHERE user_id = :uid AND status = 'enrolled'
  │
  ├──► If enrolledCount == 0:
  │      └── Emits alert: "You are not officially enrolled yet." -> Blocks LMS access
  │
  └──► If enrolledCount > 0:
         └── Establishes session, sets $_SESSION['user_role'] = 'student', redirects to /sia/lms/student/dashboard.php
```
*Architectural Implication:* LMS access is not governed by an LMS account status column. It is dynamically derived from the Enrollment System's `applications.status` column.

### 4.2 The Scheduler Instructor String Disconnect Chain
```text
Scheduler Interface (/sia/admin/scheduler/college_sections.php)
  │
  ▼
SchedulerController::collegeSections() (line 525)
  │ (Extracts $_POST['schedules'][$i]['instructor'])
  ▼
SQL: UPDATE college_section_subjects SET instructor = ? WHERE id = ?
  │ (Writes unvalidated raw text e.g. "Dr. Alan Turing")
  ▼
college_section_subjects table
  │ (Stores VARCHAR(150) loose string; NO foreign key to users.id)
  ▼
LmsAdminController::courseGenerator() (lines 19, 31)
  │ (Queries css.instructor as old_instructor_string)
  ▼
Admin UI (/sia/admin/lms/generator)
  └── Displays old_instructor_string as static text; requires admin to manually select faculty_user_id from dropdown!
```

---

### Phase 4 Dependency Tracing Conclusion
The forensic trace proves that while the `users` table contains zero dedicated LMS columns, the LMS is critically reliant on `users.id`, `users.student_number`, `users.role`, `users.password`, `users.is_active`, and `users.first_name`/`last_name`. The most critical technical friction exists in:
1. The dynamic coupling between `applications.status = 'enrolled'` and LMS student access.
2. The loose-string gap between `college_section_subjects.instructor` and `lms_courses.faculty_user_id`.
3. The destructive `ON DELETE CASCADE` foreign key on `lms_courses.faculty_user_id`.
