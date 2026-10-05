# TTU Enrollment System — Phase 0: Cashier Safety Fixes Report

## 1. Executive Summary

Phase 0 of the Cashier Architecture remediation has been successfully implemented and verified. This phase addressed foundational race conditions, database integrity gaps, missing authorization assertions, and lack of transaction/upload safety in the TTU Enrollment System without introducing external payment dependencies or altering the authoritative Registrar matriculation boundary ([ADR-008](file:///c:/xampp/htdocs/sia/docs/obsidian/Architecture/Decisions/ADR-008-applicant-to-student-transition.md)).

All 7 targeted Phase 0 objectives were achieved, tested, and validated with zero regressions to the existing enrollment lifecycle.

---

## 2. Changes Made

1. **Atomic & Connection-Scoped Receipt Generator**:
   - Refactored [`generateAtomicReceiptNumber()`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php#L858-L882) in `app/Helpers/functions.php`.
   - Replaced the vulnerable increment-then-select pattern with MariaDB/MySQL's atomic `UPDATE receipt_sequences SET current_value = LAST_INSERT_ID(current_value + 1) WHERE sequence_year = :year`.
   - The sequence increment is now evaluated under an exclusive row lock and captured via the strictly connection-scoped `LAST_INSERT_ID()`, mathematically guaranteeing that concurrent threads cannot receive identical receipt numbers.

2. **Receipt Number Database UNIQUE Constraint**:
   - Added a hard unique constraint [`uq_payment_records_receipt_number`](file:///c:/xampp/htdocs/sia/database/schema.sql#L860) on `payment_records(receipt_number)` in the live database, `database/schema.sql`, and `schema_dump.sql`.
   - Prevents duplicate receipt numbers from ever being written to the database under any race conditions.

3. **External Reference Number Uniqueness Protection**:
   - Added unique constraint [`uq_payment_records_reference_number`](file:///c:/xampp/htdocs/sia/database/schema.sql#L861) on `payment_records(reference_number)` in the live database, `database/schema.sql`, and `schema_dump.sql`.
   - Permitted multiple `NULL` values so Over-The-Counter (OTC) cash payments without external reference numbers operate without restriction.
   - Enforced pre-insert application-level locking checks in [`ApplicantController::processPayment()`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php#L457-L467) to block duplicate external reference numbers with user-friendly error messages before hitting the database constraint.

4. **Overpayment Validation in Cashier Verification**:
   - Added strict remaining balance and overpayment validation inside [`FinanceController::process()`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php#L474-L482) under the `verify_online_payment` action.
   - Blocks cashiers from approving payments exceeding the remaining balance (`$amount > $balance + 0.01`) or approving payments on already settled accounts (`$balance <= 0`), preventing negative student balances.

5. **Granular Authorization Enforcement**:
   - Added [`requirePermission('payments.record')`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php#L295) at the entry point of [`FinanceController::process()`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php#L287).
   - Closes the authorization loophole where any general admin role could submit payment mutations without holding the explicit Cashier permission.

6. **Pessimistic Row Locking and Transaction Safety in Proof Uploads**:
   - Re-architected [`ApplicantController::processPayment()`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php#L389-L553) to execute inside a managed PDO transaction (`$pdo->beginTransaction()` ... `$pdo->commit()`).
   - Implemented `SELECT ... FOR UPDATE` row locks on `student_assessments` and `payment_records` during submission.
   - Serializes concurrent submissions for the same student assessment and dynamically recalculates unverified pending payments to prevent concurrent duplicate submissions.

7. **Transactional Upload File Cleanup**:
   - Implemented automatic file rollback and cleanup in the `catch (\Throwable $e)` block of [`ApplicantController::processPayment()`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php#L532-L547).
   - If a database query, constraint check, activity log, or transaction commit fails after a proof image has been moved to `uploads/payments/` or `app/uploads/payments/`, the uploaded files are immediately deleted via `@unlink()`, leaving zero orphaned files on disk.

---

## 3. Files Changed

| File Path | Description of Changes |
| :--- | :--- |
| [`app/Helpers/functions.php`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php) | Rewrote `generateAtomicReceiptNumber()` using `UPDATE ... SET current_value = LAST_INSERT_ID(current_value + 1)` and connection-scoped `LAST_INSERT_ID()` retrieval. |
| [`app/Controllers/Admin/Finance/FinanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php) | Added `requirePermission('payments.record')` in `process()`; added overpayment and settled balance validation in `verify_online_payment`. |
| [`app/Controllers/ApplicantController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php) | Wrapped `processPayment()` in PDO transaction with `FOR UPDATE` row locks; added duplicate reference checks; added `@unlink` file cleanup on error. |
| [`database/schema.sql`](file:///c:/xampp/htdocs/sia/database/schema.sql) | Added `uq_payment_records_receipt_number` and `uq_payment_records_reference_number` UNIQUE keys. |
| [`schema_dump.sql`](file:///c:/xampp/htdocs/sia/schema_dump.sql) | Synchronized schema dump with the two unique keys. |
| [`docs/obsidian/04 - Database/Data Dictionary.md`](file:///c:/xampp/htdocs/sia/docs/obsidian/04%20-%20Database/Data%20Dictionary.md) | Documented the unique constraints on `payment_records.receipt_number` and `payment_records.reference_number`. |
| [`scripts/migrations/phase0_cashier_safety.php`](file:///c:/xampp/htdocs/sia/scripts/migrations/phase0_cashier_safety.php) | Migration script that added the UNIQUE keys on the active MariaDB database. |
| [`scripts/tests/test_phase0_safety_suite.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase0_safety_suite.php) | Automated integration test suite validating all Phase 0 security, concurrency, and integrity invariants. |

---

## 4. Database Changes

Executed on database `sia`:
```sql
ALTER TABLE `payment_records` 
  ADD UNIQUE KEY `uq_payment_records_receipt_number` (`receipt_number`),
  ADD UNIQUE KEY `uq_payment_records_reference_number` (`reference_number`);
```

* Index `uq_payment_records_receipt_number`: Prevents duplicate receipt numbers across all payment records.
* Index `uq_payment_records_reference_number`: Prevents duplicate external/bank/GCash reference numbers, while allowing multiple `NULL` values for Over-The-Counter (OTC) cash payments.

---

## 5. Tests Performed and Results

All verification tests were executed against the live MariaDB database and local PHP environment using [`scripts/tests/test_phase0_safety_suite.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase0_safety_suite.php) and [`scripts/tests/test_enrollment_bot.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_enrollment_bot.php).

### Automated Safety Test Suite Results
```text
====================================================================
  TTU ENROLLMENT SYSTEM — PHASE 0 CASHIER SAFETY TEST SUITE
====================================================================

[TEST 1] Concurrent Receipt Generation across separate DB connections...
  [PASS] 50 interleaved receipts generated without any collisions
  [PASS] Receipt format matches REC-YYYYMMDD-XXXX

[TEST 2] Database UNIQUE constraint on receipt_number...
  [PASS] DB blocks duplicate receipt_number via UNIQUE constraint

[TEST 3] Uniqueness protection for payment_records.reference_number...
  [PASS] DB blocks duplicate reference_number via UNIQUE constraint
  [PASS] Multiple NULL reference numbers are permitted (OTC cash compatibility)

[TEST 4] Overpayment validation in verify_online_payment...
  [PASS] verify_online_payment rejects payment exceeding remaining balance

[TEST 5] Permission enforcement on FinanceController::process()...
  [PASS] FinanceController::process() blocks users lacking 'payments.record' with 403

[TEST 6] Concurrency and duplicate proof submission in ApplicantController...
  [PASS] First payment proof submission succeeds
  [PASS] Duplicate reference proof submission is blocked with descriptive error

[TEST 7] Transaction rollback and file cleanup when database operation fails...
  [PASS] No orphaned proof files remain in uploads/payments/ on failure

[TEST 8] PHP Syntax Verification...
  [PASS] All touched PHP files have valid syntax

====================================================================
  TEST SUMMARY: 11 PASSED, 0 FAILED
====================================================================
```

### End-to-End Enrollment Regression Test
Executed [`scripts/tests/test_enrollment_bot.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_enrollment_bot.php):
* Simulated complete applicant lifecycle: Registration $\rightarrow$ Application $\rightarrow$ Medical $\rightarrow$ Documents $\rightarrow$ Admissions $\rightarrow$ Assessment $\rightarrow$ Cashier Payment $\rightarrow$ Registrar Finalization $\rightarrow$ LMS Account Activation.
* Result: **ALL 9 STEPS PASSED WITH ZERO REGRESSIONS**. Payment verified with atomic receipt `REC-20261002-0421`, student matriculated with student number `2026-000023`, institutional email `alex.quantum99281@ttu.edu.ph`, and 4 LMS courses provisioned.

---

## 6. Remaining Known Issues (Scheduled for Subsequent Phases)

1. **No External Payment Gateway**: The system currently only supports manual proof uploads and OTC cash payments. PayMongo integration remains to be designed in Phase 1.
2. **Missing Gateway Schema Fields**: `payment_records` does not yet have columns for `gateway_payment_intent_id`, `gateway_checkout_id`, `gateway_fee`, or webhook event logs.
3. **Queue Infrastructure**: No high-concurrency waiting room or tokenized queue tables exist yet.
4. **Printable Receipt Template**: The printed receipt view [`app/Views/admin/finance/receipt.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/finance/receipt.php) currently derives display lines from the assessment header rather than dynamically iterating the snapshot table `assessment_items`.

---

## 7. Confirmation of Architectural Scope

As strictly required by the Phase 0 instructions:
* **PayMongo implementation was NOT started.**
* **Payment queue implementation was NOT started.**
* **`PaymentService` and `PaymentRepository` were NOT created.**
* **The enrollment finalization architecture was NOT changed.**
* **[`EnrollmentService::finalizeEnrollment()`](file:///c:/xampp/htdocs/sia/app/Services/EnrollmentService.php) was NOT modified.**
* **No unrelated refactoring was performed.**

The Cashier subsystem is now safe, concurrency-protected, and ready for Phase 1 architectural planning.
