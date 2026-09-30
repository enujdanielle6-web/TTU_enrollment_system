# LMS Gap Analysis & Root Cause Diagnostic

This dossier identifies the architectural, security, and functional gaps separating the current LMS implementation from a production-grade, tightly integrated university system.

---

## 1. Current System vs. Target System Matrix

| Architectural Dimension | Current Implementation | Target Architectural State | Gap Severity |
| :--- | :--- | :--- | :--- |
| **Enrollment ↔ LMS Handshake** | JIT write inside repository read queries, uncoordinated manual admin generator, and partial sync in schedule builder. | **Deterministic Event-Driven Provisioning**: `EnrollmentService::finalizeEnrollment` guarantees student course links; `SchedulerController` provisions shells atomically upon schedule confirmation. | **HIGH** |
| **Student Course Authorization** | Gated on `applications.status IN ('enrolled', 'approved')` in `LmsAuthController` and `CollegeEnrollmentRepository`. | **Strict Registrar Gate**: Only `applications.status = 'enrolled'` confers course access. Unenrolled applicants receive clear guidance. | **CRITICAL** |
| **Route Protection & RBAC** | All LMS routes share a single group in [web.php](file:///c:/xampp/htdocs/sia/app/Routes/web.php) with only `AuthMiddleware`. No role middleware applied. | **Explicit Role Segregation**: `/lms/student/*` protected by `RoleMiddleware:student`; `/lms/faculty/*` protected by `RoleMiddleware:faculty`. | **HIGH** |
| **Material File Storage** | Escapes project root into `c:\xampp\storage\lms_materials/`. [DownloadController](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php) expects `app/uploads/lms/`. All material downloads fail. | **Unified Isolated Storage**: Centralized directory `storage/uploads/lms/materials/` with safe MIME validation, streaming, and IDOR protection. | **CRITICAL** |
| **Session Role Keys** | Inconsistent: `DownloadController` checks `$_SESSION['role']` which does not exist (`$_SESSION['user_role']` is set). | **Standardized Session Contract**: Single canonical session role attribute (`$_SESSION['user_role']`). | **HIGH** |
| **Gradebook Performance** | Personal gradebook recalculates the entire course grid for all students on every load ($O(N \times M)$ queries). | **Scoped Student Query**: `getStudentGradebook()` queries only the single student's submissions and attempts ($O(1)$ query complexity). | **MEDIUM** |
| **Gradebook Eligibility Filter** | `LmsGradebookService` queries `college_enrollments` without checking `applications.status = 'enrolled'`. | **Active Matriculation Filter**: Joins `applications a ON ce.application_id = a.id WHERE a.status = 'enrolled'`. | **HIGH** |
| **Irregular Student Roster Sync** | `LmsService::getFacultyCourses` subquery reads `applications.section_id = lc.academic_section_id`, omitting irregular students. | **Polymorphic Roster Count**: Counts distinct students from `college_enrollments` / `shs_enrollments` matching the section and subject. | **MEDIUM** |
| **Administrative Governance** | Single generator view `/admin/lms/generator`. No course overview, re-assignment, or term rollover. | **Comprehensive LMS Admin Module**: Course catalog, faculty reassignment, term rollover, and audit log tracking. | **MEDIUM** |

---

## 2. Root Cause Diagnostics (Top 10 Architectural Problems)

### Problem 1: Broken Material Upload and File Delivery Subsystem
* **Why it happens**: [FacultyController.php:90](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php#L90) navigates up 4 directory levels: `__DIR__ . '/../../../../storage/lms_materials/'`. Because the controller is at `app/Controllers/Lms/`, this path points outside the web application (`c:\xampp\storage\lms_materials/`). Meanwhile, [DownloadController.php:56](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php#L56) reads from `app/uploads/lms/`. Furthermore, `DownloadController:21` checks `$_SESSION['role']` which is never set.
* **Affected Component**: `FacultyController@uploadMaterial`, `DownloadController@downloadMaterial`, `DownloadController@downloadSubmission`.
* **Affected Users**: Faculty instructors (materials upload to wrong place), students (cannot download lecture materials or submission files).
* **Current Behavior**: File uploads escape project root; all file download requests return HTTP 403 Forbidden or HTTP 404 Not Found.
* **Expected Behavior**: Materials upload to an application-managed directory; authenticated students and faculty stream files safely with verified course access.
* **Recommended Architectural Fix**:
  1. Define a global storage constant `LMS_UPLOAD_DIR = dirname(__DIR__, 2) . '/storage/uploads/lms'`.
  2. Fix `DownloadController` to check `$_SESSION['user_role']`.
  3. Ensure upload directory creation and `.htaccess` protection preventing script execution.

---

### Problem 2: Premature LMS Access for Unenrolled Applicants
* **Why it happens**: [LmsAuthController.php:96](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php#L96) checks `a.status IN ('enrolled', 'approved')`.
* **Affected Component**: `LmsAuthController@loginProcess`.
* **Affected Users**: Unenrolled applicants whose documents were verified by Admissions but who have not paid tuition or been finalized by the Registrar.
* **Current Behavior**: Applicant can log in to the Student LMS portal. Once inside, `LmsService::getStudentRepository` requires `status = 'enrolled'`, rendering an empty, broken dashboard.
* **Expected Behavior**: Only students with `applications.status = 'enrolled'` can access the LMS. Applicants receive a polite redirect explaining that cashier payment and registrar finalization are required.
* **Recommended Architectural Fix**:
  1. Change `LmsAuthController` query to `a.status = 'enrolled'`.
  2. Display flash warning: *"Your application is approved but enrollment is pending tuition verification. Please complete cashier payment to activate LMS access."*

---

### Problem 3: Missing Route-Level Role Middleware on LMS Endpoints
* **Why it happens**: In [app/Routes/web.php:181](file:///c:/xampp/htdocs/sia/app/Routes/web.php#L181), student, faculty, and download routes are grouped under:
  ```php
  $router->group(['middleware' => ['SessionSecurityMiddleware', 'CsrfMiddleware', 'AuthMiddleware']], function (Router $router) { ... });
  ```
  Neither `RoleMiddleware:student` nor `RoleMiddleware:faculty` is attached to the route definitions.
* **Affected Component**: Entire `/lms/student/*` and `/lms/faculty/*` route hierarchy.
* **Affected Users**: All system roles (Admissions, Cashier, Clinic, Students, Faculty).
* **Current Behavior**: Any authenticated user (e.g. a cashier or admissions officer) can type `/lms/student/dashboard.php` or `/lms/faculty/dashboard.php` and load the controller without a 403 Forbidden error.
* **Expected Behavior**: Role middleware rejects unauthorized roles before controller execution begins.
* **Recommended Architectural Fix**:
  1. Split the LMS route group into two distinct groups in `web.php`:
     - Group 1: `/lms/student/*` with `RoleMiddleware:student`.
     - Group 2: `/lms/faculty/*` with `RoleMiddleware:faculty`.
  2. Allow `admin` and `superadmin` oversight bypass via `RoleMiddleware`.

---

### Problem 4: JIT Database Mutation Inside Repository Read Methods
* **Why it happens**: [CollegeEnrollmentRepository.php:117-125](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php#L117-L125) and [ShsEnrollmentRepository.php:114-122](file:///c:/xampp/htdocs/sia/app/Repositories/ShsEnrollmentRepository.php#L114-L122) execute an `INSERT INTO lms_courses` statement during `getActiveStudentCourses()`, which is called during HTTP `GET` requests.
* **Affected Component**: `CollegeEnrollmentRepository`, `ShsEnrollmentRepository`.
* **Affected Users**: System performance, database concurrency.
* **Current Behavior**: Visiting the student dashboard triggers database writes, altering sequence IDs and causing write contention during read operations.
* **Expected Behavior**: Repository methods are pure reads. Course shells are provisioned during schedule builder setup or administrative generation.
* **Recommended Architectural Fix**:
  1. Remove `INSERT INTO lms_courses` from repository read loops.
  2. Ensure all timetable section-subjects have `lms_courses` shells provisioned upon schedule finalization.

---

### Problem 5: Omission of Courses Lacking Assigned Faculty
* **Why it happens**: In `CollegeEnrollmentRepository.php:140-142`, if no faculty is assigned to a section subject in timetable (`college_section_subjects.faculty_user_id` is null or TBA), the loop executes `else { continue; }`.
* **Affected Component**: `CollegeEnrollmentRepository`, `ShsEnrollmentRepository`.
* **Affected Users**: Enrolled students taking subjects that do not yet have an assigned faculty instructor.
* **Current Behavior**: The subject completely disappears from the student's LMS dashboard and courses list. The student cannot verify that they are enrolled in the course.
* **Expected Behavior**: The course card renders with an "Instructor: TBA / Unassigned" badge so the student sees their complete enrolled study load.
* **Recommended Architectural Fix**:
  1. Allow `lms_courses.faculty_user_id` to be `NULL` (or assign a default system placeholder user `1`).
  2. Render course shell with "Instructor TBA" indicator instead of skipping the enrolled subject.

---

### Problem 6: Inconsistent Enrolled Student Counting (Irregular Student Invisibility)
* **Why it happens**: In [LmsService.php:79-83](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php#L79-L83), `getFacultyCourses` counts students via:
  ```sql
  SELECT COUNT(DISTINCT a.user_id) FROM applications a 
  WHERE a.section_id = lc.academic_section_id AND a.status IN ('enrolled', 'approved')
  ```
  Irregular students have `applications.section_id = NULL` (or only one section), but are enrolled in specific subjects via `college_enrollments.college_section_id`.
* **Affected Component**: `LmsService::getFacultyCourses`, `FacultyController@dashboard`.
* **Affected Users**: Faculty instructors teaching irregular students.
* **Current Behavior**: Faculty dashboard card displays an inaccurate student count that excludes irregular students. However, when the faculty opens the Gradebook, the irregular students appear.
* **Expected Behavior**: Dashboard enrolled student metrics match the actual gradebook roster.
* **Recommended Architectural Fix**:
  1. Refactor the enrolled count subquery in `LmsService::getFacultyCourses` to count from `college_enrollments` / `shs_enrollments`:
  ```sql
  SELECT COUNT(DISTINCT a.user_id) 
  FROM college_enrollments ce
  JOIN applications a ON ce.application_id = a.id
  WHERE ce.college_section_id = lc.academic_section_id 
    AND ce.subject_id = lc.subject_id 
    AND a.status = 'enrolled'
  ```

---

### Problem 7: Unbounded Gradebook Query Storm ($O(N \times M)$)
* **Why it happens**: [LmsGradebookService.php:142](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php#L142) implements `getStudentGradebook($lmsCourseId, $studentId)` by calling `getCourseGradebook($lmsCourseId)` (which fetches all students, all submissions, and all quiz attempts across the entire class), and then filtering for `if ($row['student']['id'] == $studentId)`.
* **Affected Component**: `LmsGradebookService`, `StudentGradebookController`.
* **Affected Users**: Students accessing personal grades; database server under concurrent student load.
* **Current Behavior**: In a class of 45 students with 10 assignments and 5 quizzes, a single student viewing their grades executes hundreds of database queries across 44 other students' records.
* **Expected Behavior**: Student gradebook executes a single scoped query fetching only the logged-in student's grades.
* **Recommended Architectural Fix**:
  1. Implement dedicated `getStudentPersonalGradebook($lmsCourseId, $studentId)` querying only the student's records in a single query.

---

### Problem 8: Absence of Registrar Drop/Withdrawal Propagation
* **Why it happens**: TTU Enrollment has no formal drop subject workflow. If an administrator deletes a record in `college_enrollments`, the course access disappears, but existing student submissions, quiz attempts, and attendance marks remain orphaned.
* **Affected Component**: Database relational integrity, `lms_submissions`, `lms_quiz_attempts`.
* **Affected Users**: Registrar officers, faculty grading queues.
* **Current Behavior**: No audit trail or state transition for dropped subjects. Dropped students disappear from gradebook, but their submissions remain in the faculty grading queue.
* **Expected Behavior**: A formal subject drop mechanism transitions the enrollment record to `status = 'dropped'`. LMS course shells respect the dropped status and archive associated task submissions.
* **Recommended Architectural Fix**:
  1. Add `status ENUM('enrolled', 'dropped', 'withdrawn')` to `college_enrollments` and `shs_enrollments`.
  2. Filter active LMS views by `ce.status = 'enrolled'`.

---

### Problem 9: Section Reassignment Desynchronization
* **Why it happens**: When a student is assigned to Section A, `college_enrollments.college_section_id` is set to Section A. If an administrator subsequently alters `applications.section_id` to Section B, `college_enrollments` is not updated.
* **Affected Component**: `EnrollmentService`, `AdmissionsController`, `RegistrarController`.
* **Affected Users**: Students transferred between sections; faculty rosters.
* **Current Behavior**: Student's application says Section B, but their LMS courses remain locked to Section A.
* **Expected Behavior**: Section reassignment updates `college_enrollments.college_section_id` and rebinds LMS courses cleanly.
* **Recommended Architectural Fix**:
  1. Build an atomic section transfer service method that updates `applications.section_id` and `college_enrollments.college_section_id` within a single database transaction.

---

### Problem 10: Complete Lack of LMS Administrative Management Tooling
* **Why it happens**: LMS development historically focused exclusively on student and faculty interfaces. The only admin feature built is `/admin/lms/generator`.
* **Affected Component**: System administration, course lifecycle governance.
* **Affected Users**: Deans, department chairs, LMS administrators.
* **Current Behavior**: Administrators cannot view all courses, reassign instructors when faculty take leave, unpublish courses, view system-wide submission rates, or archive old academic terms.
* **Expected Behavior**: Comprehensive LMS admin portal with course oversight, instructor reassignment, term rollover, and system activity monitoring.
* **Recommended Architectural Fix**:
  1. Expand `LmsAdminController` with full CRUD: `index`, `show`, `editFaculty`, `archive`, `syncEnrollments`.
