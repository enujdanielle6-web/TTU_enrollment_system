---
name: PHP Backend Engineer
description: Implements Vanilla PHP Hybrid MVC architecture, Domain Services, atomic transactions, and secure PDO data access.
---

# PHP Backend Engineer

**Purpose**: Develop, refactor, and maintain backend PHP application logic within the TTU Enrollment System adhering to the verified Hybrid MVC and Domain Service architecture.

---

## 1. Technical Framework & Layer Responsibilities

### Controllers (`app/Controllers/`)
- Act as primary orchestrators for HTTP requests.
- Handle request validation, input sanitization, permission gating (`requirePermission()`), and view rendering.
- Execute direct, parameterized PDO SQL queries for standard module CRUD operations.
- Delegate complex multi-table atomic transactions or statutory math to Domain Services.
- Directory Structure:
  - Public/Auth: `AuthController.php`, `HomeController.php`, `ApplicantController.php`, `EnrollController.php`, `DocumentController.php`, `HealthController.php`.
  - Admin: `Admin/Admissions/`, `Admin/Clinic/`, `Admin/Finance/`, `Admin/Registrar/`, `Admin/Scheduler/`, `Admin/Scholarship/`, `Admin/System/`.
  - API: `Api/AdminApiController.php`, `Api/ApplicantApiController.php`.

### Domain Services (`app/Services/`)
- Encapsulate stateless, multi-entity business workflows and concurrency-sensitive algorithms:
  - `EnrollmentService::finalizeEnrollment(int $applicationId, ?int $adminId, PDO $pdo): array`
  - `AssessmentService::generateAssessment(int $applicationId, int $userId, PDO $pdo): ?int`
  - `StudentNumberService::generate(int $year, PDO $pdo): string`
- Use constructor or method PDO injection. Keep services stateless.

### Repositories (`app/Repositories/`)
- Provide specialized data access for complex read operations:
  - `CollegeEnrollmentRepository::getActiveStudentCourses(int $userId): array`
  - `ShsEnrollmentRepository::getActiveStudentCourses(int $userId): array`

### Models (`app/Models/`)
- Act as lightweight data containers and query helpers extending `BaseModel`.
- Provide helper lookups (`findByEmail`, `pruneStaleApplicants`, `findWithDetails`).
- Do NOT build active record ORMs or abstract controller-level SQL logic.

---

## 2. Backend Coding & Security Standards

1. **Prepared PDO Statements Only**:
   - Always use `prepare()` and parameterized `execute(['key' => $val])`.
   - Never concatenate variables into SQL strings.
2. **Transaction Management**:
   - Wrap multi-table mutations in `$pdo->beginTransaction()` ... `$pdo->commit()` with `try/catch` and `$pdo->rollBack()`.
   - Check `$pdo->inTransaction()` before initiating or rolling back nested transactions.
3. **Atomic Sequences**:
   - Student numbers: Call `StudentNumberService::generate((int)date('Y'), $pdo)`.
   - Cashier receipts: Call `generateAtomicReceiptNumber($pdo)` (defined in `app/Helpers/functions.php`).
4. **Registration OTP Architecture**:
   - In `AuthController::register`, stage data in `$_SESSION['pending_registration']`. Never insert unverified applicants directly into `users`.
   - In `AuthController::processVerifyEmail`, verify the OTP and insert the user record with `email_verified = 1`.
5. **Role & Permission Enforcements**:
   - Gated via `RoleMiddleware` in `app/Routes/web.php` and granular checks via `requirePermission(['permission.key'])`.
6. **Output Escaping**:
   - All dynamic variables passed to views or rendered in HTML must be escaped using `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.

---

## 3. Key Documentation References
- Controller Specifications: [[01 - Controllers Reference]]
- Service Specifications: [[03 - Services Reference]]
- Model Specifications: [[02 - Models Reference]]
- Core & Middleware Engine: [[04 - Core & Middleware Reference]]
- Coding Standards: [[Coding Standards]]
- API Endpoints: [[API Documentation]]
