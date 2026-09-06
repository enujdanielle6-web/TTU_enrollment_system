# ADR-010: Domain Service Layer Extraction and Atomic Sequences

## Status
**Accepted**

## Date
2026-09-06

## Context
Under the TTU Hybrid MVC paradigm, controllers operate as "Fat Controllers" directly managing request handling, validation, business orchestration, and raw PDO SQL queries. However, as business workflows expanded across Admissions, Finance, and Registrar modules, several core domain operations suffered from logic fragmentation and concurrency hazards:

1. **Duplicate Domain Logic:** Student matriculation, tuition math calculation, and student number generation were duplicated across `AdmissionsController`, `FinanceController`, and `RegistrarController`.
2. **Student Number Collisions:** Student numbers were originally generated using heuristic queries such as `SELECT COUNT(*) FROM users WHERE student_number LIKE 'YYYY-%'`. Under concurrent admissions approvals or finalizations, two concurrent transactions reading the same `COUNT(*)` generated identical student numbers, triggering unique constraint violations or corrupted student records.
3. **Complex Enrollment Transactions:** Finalizing an enrollment requires multiple distinct mutations: updating application status, generating student IDs, provisioning `@ttu.edu.ph` institutional emails, enforcing password resets, populating section enrollments, and sending welcome emails.

## Decision
We introduced a **Lightweight Domain Service Layer** located in `app/Services/` that extracts complex, cross-cutting business rules into focused, stateless service classes while preserving the Hybrid MVC Fat Controller pattern:

1. **`App\Services\StudentNumberService`:**
   - Provides atomic, race-free student ID generation formatted as `YYYY-XXXXXX` (e.g. `2026-000001`).
   - Relies on the dedicated `student_number_sequences` database table (`year` INT PRIMARY KEY, `current_sequence` INT, `updated_at` TIMESTAMP).
   - Executes row-level locking via `SELECT current_sequence FROM student_number_sequences WHERE year = ? FOR UPDATE` within the caller's PDO transaction.
2. **`App\Services\AssessmentService`:**
   - Encapsulates authoritative tuition and fee calculation math for both College and Senior High School programs.
   - Evaluates lecture units, lab fees, miscellaneous fees, and per-unit billing rates.
   - Provides itemized breakdown structures for snapshotting into `assessment_items`.
3. **`App\Services\EnrollmentService`:**
   - Authoritative orchestrator for the matriculation transaction.
   - Executes atomic state transition from `payment_verified` to `enrolled`.
   - Coordinates `StudentNumberService`, provisions unique `@ttu.edu.ph` email addresses, creates college/SHS section enrollments, updates `force_password_reset = 1`, and queues welcome emails with credentials.

```text
Fat Controller (RegistrarController / AdmissionsController)
    │
    ├── Request Parsing & CSRF Validation
    ├── Role & Permission Verification
    ├── Database Transaction Management (beginTransaction / commit / rollBack)
    │
    ├── Calls Domain Services:
    │     ├── StudentNumberService::generate($year, $pdo)
    │     ├── AssessmentService::calculate($application, $feeTemplate, $pdo)
    │     └── EnrollmentService::finalizeEnrollment($applicationId, $registrarId, $pdo)
    │
    └── Response Rendering & Flash Messages
```

## Consequences

### Positive
- **Guaranteed Uniqueness:** Zero student number or receipt collisions under high concurrent admissions load.
- **DRY Business Logic:** Tuition formulas and matriculation workflows have a single authoritative implementation.
- **Hybrid MVC Integrity Preserved:** Controllers remain the primary entry point and orchestrator, maintaining direct PDO control and avoiding bloated ORM layers.

### Trade-offs
- Introduces service dependencies within controller methods.
- Mitigated by keeping services strictly stateless with constructor or static PDO parameter injection.

---
**Related:**
- [[MVC Strangler Fig Migration]]
- [[System Architecture]]
- [[Registrar]]
- [[Finance]]
- [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]]
- [[ADR-009 Financial Immutability and Assessment Snapshots]]
