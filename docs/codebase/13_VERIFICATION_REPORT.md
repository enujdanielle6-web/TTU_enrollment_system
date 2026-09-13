# 13. VERIFICATION REPORT

## 1. What Is Confidently Understood
- The entire MVC architecture, Front Controller routing, middleware pipeline, and session lifecycle.
- The applicant lifecycle from registration to student number assignment, institutional email generation, and section subject enrollment.
- The LMS course structure, assignment pipeline, timed quiz engine, and gradebook formulas.
- The exact point of breakdown between the Enrollment Scheduler and LMS course assignment.

## 2. What Was Verified Against Source Code
- `SystemController.php:162` whitelist strictly rejects role `'faculty'`.
- `DownloadController.php:21, 89` queries `$_SESSION['role']`, which is never defined in `AuthController.php` or `LmsAuthController.php`.
- `FacultyController.php:90` constructs an upload path that escapes into `c:\xampp\storage\lms_materials/`.
- `web.php:178` routes all LMS endpoints under `AuthMiddleware` without role separation.
- `app/Views/lms/*` forms completely omit CSRF token inputs.
- `cashier_payments.php:9-24` executes raw `$pdo->query(...)` within the presentation markup.
- `LmsQuizService.php:196-203` time limit validation block is empty.

## 3. What Was Corrected During Verification (False Positives Removed)
- **SQL Injection in Scheduler:** Dynamic `$table` and `$secIdCol` variables in `SchedulerController` were verified to be internally assigned via ternary operations based on boolean conditions, not from client request parameters.
- **Document Traversal:** `DocumentController` was verified to strictly validate extensions, inspect MIME types via `finfo`, and generate safe internal filenames.
- **Clinic Permissions:** `ClinicController` was verified to correctly call `requirePermission('medical.review')` across its actions.

## 4. Architectural Summary
The TTU platform possesses a strong modern core (Router, Middleware, Domain Services) coupled with legacy procedural roots that are currently undergoing a Strangler Fig migration. Resolving the operational blockers identified in the verification report will restore full end-to-end functionality across Enrollment and LMS.
