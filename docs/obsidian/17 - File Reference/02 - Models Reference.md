# Models Reference Manual

This document provides complete, verified file-level documentation for all **10 Model classes** in the `app/Models/` namespace.

In accordance with TTU's **Hybrid MVC architecture**, Models act primarily as **basic data containers and lightweight ORM query helpers**, avoiding abstracting away or hiding complex SQL logic meant for controllers.

---

### `BaseModel.php`
- **File:** `BaseModel.php`
- **Path:** `app/Models/BaseModel.php`
- **Module:** Core Model Engine
- **Purpose:** Abstract base class providing standardized CRUD helper methods across database tables.
- **Responsibilities:**
  - Acquires database PDO instances via `App\Core\Database::getConnection()`.
  - Executes generic single-record lookups, multi-record selections, parameterized `WHERE` filters, and CRUD mutations.
- **Key Methods:**
  - `static find(int $id): ?array` — Fetches a single record by primary key `id`.
  - `static all(): array` — Returns all records from the model's bound table.
  - `static where(string $column, mixed $value): array` — Executes parameterized `SELECT * WHERE column = ?`.
  - `static create(array $data): int` — Generates dynamic parameterized `INSERT` query and returns the inserted `lastInsertId`.
  - `static update(int $id, array $data): bool` — Generates parameterized `UPDATE table SET ... WHERE id = ?`.
  - `static delete(int $id): bool` — Deletes record by primary key `id`.
- **Dependencies:** `App\Core\Database`, PDO
- **Used By:** Extended by child models (`User`, `Application`, `StudentAssessment`, etc.)
- **Related Documentation:** [[MVC Strangler Fig Migration]], [[Coding Standards]]

---

### `User.php`
- **File:** `User.php`
- **Path:** `app/Models/User.php`
- **Module:** Identity & Authentication
- **Purpose:** Data model and authentication query helper for institutional accounts in `users`.
- **Responsibilities:**
  - Queries user records by primary email address, ensuring `email_verified` flag is fetched.
  - Records and manages failed login attempts in `login_attempts` with IP throttling.
  - Prunes expired login attempt logs and abandoned unverified applicant registrations.
  - Dispatches institutional audit logging entries into `activity_logs`.
- **Key Methods:**
  - `static findByEmail(string $email): ?array` — Fetches active user account by email address (includes `email_verified`, `force_password_reset`).
  - `static recordFailedAttempt(string $email, string $ip): void` — Logs failed authentication attempt.
  - `static clearFailedAttempts(string $email, string $ip): void` — Clears failed attempt counter upon successful login.
  - `static pruneStaleAttempts(int $minutes = 15): void` — Purges old records from `login_attempts`.
  - `static updateLastLogin(int $userId): void` — Updates `last_login` timestamp.
  - `static logActivity(int $userId, string $title, string $desc, string $icon, ?string $record): void` — Inserts entry into `activity_logs`.
  - `static pruneStaleApplicants(): int` — Purges unverified accounts past OTP expiry.
- **Database Tables:** `users`, `login_attempts`, `activity_logs`.
- **Used By:** `AuthController`, `SystemController`, `SessionSecurityMiddleware`.
- **Related Documentation:** [[Users Table]], [[Authentication & Email Verification]]

---

### `Application.php`
- **File:** `Application.php`
- **Path:** `app/Models/Application.php`
- **Module:** Admissions & Applicant Portal
- **Purpose:** Primary lifecycle anchor model for student term applications in `applications`.
- **Responsibilities:**
  - Queries active application records by student `user_id`.
  - Handles demographic and contact information updates.
  - Executes full multi-field creation and update routines for online applicant wizard forms.
- **Key Methods:**
  - `static findByUserId(int $userId): ?array` — Returns the current application for a given user.
  - `static updateContactDetails(int $userId, array $data): bool` — Updates phone, telephone, and home address fields.
  - `static createFull(array $data): int` — Inserts a complete new application term record.
  - `static updateFull(int $applicationId, array $data): bool` — Updates all applicant demographic and program fields.
- **Database Tables:** `applications`.
- **Used By:** `ApplicantController`, `EnrollController`, `AdmissionsController`, `RegistrarController`.
- **Related Documentation:** [[Applications Table]], [[ADR-001 The Application as Term Concept]]

---

### `ApplicationDocument.php`
- **File:** `ApplicationDocument.php`
- **Path:** `app/Models/ApplicationDocument.php`
- **Module:** Admissions & Requirements
- **Purpose:** Query model governing uploaded admission requirements in `application_documents`.
- **Responsibilities:**
  - Fetches requirements for a specific application record.
  - Checks requirement completeness flags.
  - Persists new uploaded document records with sanitized file paths.
- **Key Methods:**
  - `static findByApplicationId(int $applicationId): array` — Returns all requirement files for an application.
  - `static hasUploadedDocuments(int $applicationId): bool` — Checks if at least one document has been uploaded.
  - `static findByDocumentName(int $applicationId, string $docName): ?array` — Fetches specific requirement record (e.g. `Form 138`).
  - `static saveUpload(int $applicationId, string $docName, string $path, string $status = 'pending'): int` — Inserts or updates uploaded document.
- **Database Tables:** `application_documents`.
- **Used By:** `DocumentController`, `AdmissionsController`, `EnrollController`.
- **Related Documentation:** [[Application Documents Table]], [[Document Submission Preference Workflow]]

---

### `HealthRecord.php`
- **File:** `HealthRecord.php`
- **Path:** `app/Models/HealthRecord.php`
- **Module:** Clinic & Student Health
- **Purpose:** Data model representing student medical declarations in `health_records`.
- **Responsibilities:**
  - Retrieves clinic clearance status for application gate evaluation.
  - Saves physical measurements and medical condition declarations.
- **Key Methods:**
  - `static getStatus(int $applicationId): string` — Returns clinic status (`pending`, `under_review`, `verified`, `correction_required`).
  - `static save(int $userId, int $applicationId, array $data): int` — Upserts student health record.
- **Database Tables:** `health_records`.
- **Used By:** `HealthController`, `ClinicController`, `AdmissionsController`.
- **Related Documentation:** [[Health Submission & Clearance Workflow]], [[Clinic]]

---

### `StudentAssessment.php`
- **File:** `StudentAssessment.php`
- **Path:** `app/Models/StudentAssessment.php`
- **Module:** Finance & Cashier
- **Purpose:** Query helper for student billing ledgers in `student_assessments`.
- **Responsibilities:**
  - Retrieves active billing assessment records by application ID.
  - Provides tuition and payment totals.
- **Key Methods:**
  - `static findByApplicationId(int $applicationId): ?array` — Fetches assessment ledger including `total_amount`, `discount_amount`, `net_amount`, `total_paid`, and `payment_status`.
- **Database Tables:** `student_assessments`.
- **Used By:** `ApplicantController`, `FinanceController`, `AdmissionsController`.
- **Related Documentation:** [[Payment & Assessment Workflow]], [[ADR-009 Financial Immutability and Assessment Snapshots]]

---

### `ScholarshipApplication.php`
- **File:** `ScholarshipApplication.php`
- **Path:** `app/Models/ScholarshipApplication.php`
- **Module:** Scholarship
- **Purpose:** Query model for financial aid requests in `scholarship_applications`.
- **Responsibilities:**
  - Checks active scholarship grant review status.
- **Key Methods:**
  - `static getStatus(int $userId): ?string` — Returns status of active scholarship request (`pending`, `under_review`, `approved`, `rejected`).
- **Database Tables:** `scholarship_applications`.
- **Used By:** `ApplicantController`, `ScholarshipController`.
- **Related Documentation:** [[Scholarship]]

---

### `Schedule.php`
- **File:** `Schedule.php`
- **Path:** `app/Models/Schedule.php`
- **Module:** Scheduler & Enrollment
- **Purpose:** Domain helper evaluating subject timetable validity and class section constraints.
- **Responsibilities:**
  - Validates applicant selected subject schedules against section block schedules.
- **Key Methods:**
  - `static validateSelectedSubjects(array $subjectIds, int $sectionId): bool` — Verifies subject offerings belong to target section.
- **Database Tables:** `college_section_subjects`, `shs_section_subjects`.
- **Used By:** `EnrollController`, `ApplicantApiController`.
- **Related Documentation:** [[Scheduler]]

---

### `ActivityLog.php`
- **File:** `ActivityLog.php`
- **Path:** `app/Models/ActivityLog.php`
- **Module:** Security & System Audit
- **Purpose:** Query model for immutable administrative action logs in `activity_logs`.
- **Responsibilities:**
  - Retrieves historical audit trail logs for specific user identities.
- **Key Methods:**
  - `static findByUserId(int $userId, int $limit = 10): array` — Returns chronological list of user audit events.
- **Database Tables:** `activity_logs`.
- **Used By:** `ApplicantController`, `SystemController`.
- **Related Documentation:** [[Security Overview]], [[System Administration]]

---

### `Announcement.php`
- **File:** `Announcement.php`
- **Path:** `app/Models/Announcement.php`
- **Module:** Communications & Public Portal
- **Purpose:** Query model for university-wide broadcast notices in `announcements`.
- **Responsibilities:**
  - Fetches active announcements for display on public homepage and applicant dashboard.
- **Key Methods:**
  - `static getActiveAnnouncements(int $limit = 5): array` — Returns active notices sorted by `created_at DESC`.
- **Database Tables:** `announcements`.
- **Used By:** `HomeController`, `ApplicantController`.
- **Related Documentation:** [[Landing Page & Program Card Customization]]

---
**Related:**
- [[00 - File Reference Index]]
- [[01 - Controllers Reference]]
- [[03 - Services Reference]]
- [[04 - Core & Middleware Reference]]
