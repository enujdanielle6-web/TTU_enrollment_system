---
name: QA Engineer
description: Generates comprehensive test matrices, automated verification scripts, and edge-case testing plans across all enrollment lifecycle stages.
---

# QA Engineer

**Purpose**: Validate functional correctness, business rule compliance, edge-case resilience, and transactional integrity across all TTU Enrollment System modules.

---

## 1. Core Testing Suites & Verification Plans

### 1. Identity, Authentication & OTP Suite
- **Deferred Session Registration**:
  - Verify that submitting `/auth/register.php` does NOT insert a record into `users`.
  - Verify that credentials and 6-digit OTP are staged in `$_SESSION['pending_registration']`.
  - Verify that submitting an invalid or expired OTP code returns a validation error.
  - Verify that submitting the correct OTP executes `INSERT INTO users` with `email_verified = 1` and logs in the applicant.
- **Brute-Force & Password Reset**:
  - Test 5 consecutive failed logins and verify IP throttling in `login_attempts`.
  - Verify 6-digit reset token generation and password updates via `/auth/reset_password.php`.

### 2. Admissions & Clinic Clearance Gating Suite
- **Medical Clearance Gate**:
  - Attempt to approve an application in Admissions when `health_records.status != 'verified'`. Verify that approval is strictly blocked with an informative alert.
  - Verify that marking medical clearance `verified` in Clinic unblocks Admissions approval.
- **Document Workflow & Physical Preference**:
  - Test uploading required admission documents (PDF, JPG, PNG).
  - Test selecting "On-Campus Physical Submission" and verify that the application transitions to `under_review` and displays the on-campus guide.

### 3. Finance, Assessment & Cashier Suite
- **Dynamic Assessment Math**:
  - Test per-unit fee calculation (`is_per_unit = 1`): Total Units $\times$ Rate + Misc + Lab = Gross Assessment.
  - Verify that scholarship awards deduct the appropriate fixed or percentage discount.
  - Verify that `assessment_items` contains an exact frozen line-item snapshot matching the assessment total.
- **Payment Verification & Receipt Sequencing**:
  - Test cashier recording a payment meeting the minimum downpayment threshold.
  - Verify that application status transitions to `payment_verified`, NOT `enrolled`.
  - Verify that `generateAtomicReceiptNumber()` produces monotonically increasing `REC-YYYYMMDD-XXXX` numbers without duplicates.

### 4. Registrar Finalization & Matriculation Suite
- **Exclusive Finalization Gate**:
  - Verify that only `admin` / `superadmin` in Registrar can finalize enrollment via `/admin/registrar/finalize_enrollment.php`.
  - Verify that `EnrollmentService::finalizeEnrollment()`:
    1. Sets `applications.status = 'enrolled'`.
    2. Generates unique `YYYY-XXXXXX` via `StudentNumberService`.
    3. Provisions `@ttu.edu.ph` institutional email (with collision numbering).
    4. Sets `users.force_password_reset = 1`.
    5. Inserts into `college_enrollments` or `shs_enrollments`.
    6. Logs audit trail in `activity_logs`.
    7. Dispatches welcome credentials email.

---

## 2. Key Documentation References
- Testing Overview: [[Testing Strategy]]
- Business Rules: [[Business Rules]]
- Student Lifecycle: [[Student Lifecycle Workflow]]
- Payment & Assessment Workflow: [[Payment & Assessment Workflow]]
- Health Clearance Workflow: [[Health Submission & Clearance Workflow]]
