# 03. AUTHENTICATION AND RBAC

## Authentication Engine
- **Identity Storage:** `users` table.
- **Password Security:** Bcrypt via `password_hash(..., PASSWORD_DEFAULT)`.
- **OTP Verification:** 6-digit verification code sent via email, tracked with `verification_code_expires_at`.
- **Session Tokens:** `$_SESSION['logged_in'] = true`, `$_SESSION['user_id']`, `$_SESSION['user_role']`, `$_SESSION['user_permissions']`.
- **Brute Force Defense:** `login_attempts` table tracks failed logins and enforces lockout windows.

## Role Hierarchy
1. `superadmin`: Unrestricted institutional access (wildcard `["*"]`).
2. `admin` (Registrar): Curricula, programs, subjects, and student records.
3. `admissions`: Applications review queue and document verification.
4. `cashier`: Assessment verification, tuition collection, and official receipts.
5. `clinic`: Medical history review and health clearance.
6. `scheduler`: Section timetable creation, room assignments, and scheduling.
7. `scholarship`: Grant configuration and beneficiary evaluation.
8. `faculty`: LMS course management, module uploads, quizzes, and grading.
9. `student`: Enrolled LMS access, assignment submissions, and quiz taking.
10. `applicant`: Pre-enrollment admission portal and document submissions.

## Permissions System
Implemented in `app/Core/functions.php`:
- `hasPermission(string|array $perms)`: Evaluates user's decoded permissions JSON and role.
- `requirePermission(string|array $perms)`: Enforces permission or halts execution with 403 Forbidden.
