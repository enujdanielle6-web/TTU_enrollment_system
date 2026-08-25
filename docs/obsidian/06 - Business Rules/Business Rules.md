# Business Rules

This catalog documents the active, database-enforced, and operational business rules governing the TTU Enrollment & LMS System.

---

## 1. Authentication & Security Rules
- **Rule AUTH-01 (Email Uniqueness):** Every account in `users` must have a unique email address.
- **Rule AUTH-02 (OTP Verification Gating):** Newly registered applicants cannot log in or view the applicant dashboard until they verify their email with a valid 6-digit OTP code (`email_verified = 1`).
- **Rule AUTH-03 (OTP Expiration):** Generated verification codes expire after **15 minutes**. Submitting an expired code is rejected.
- **Rule AUTH-04 (Role Route Isolation):** Role boundaries are enforced by `RoleMiddleware`. Non-admin accounts (`applicant`, `student`) accessing `/admin/*` are blocked and redirected.
- **Rule AUTH-05 (Multi-Portal Sign-out):** Logging out from the LMS redirects directly to the appropriate LMS student or faculty login portal.

---

## 2. Admissions & Applicant Progression Rules
- **Rule ADM-01 (Unique Application Reference):** Each applicant row in `applications` receives a globally unique `reference_number`.
- **Rule ADM-02 (Sequential Gating):**
  1. Applicant cannot enroll in subjects until `applications.status = 'approved'`.
  2. Applicant cannot enroll in subjects until they have submitted their **Health Information** (`health_records`).
  3. Applicant cannot finalize enrollment without a generated financial assessment statement.
- **Rule ADM-03 (Health Clearance Requirement):** Clinic evaluation operates in parallel with admissions; applicants with missing health records are held from completing final registration.
- **Rule ADM-04 (Automated Credential Generation):** When Admissions or Cashier finalizes enrollment (`status = 'enrolled'`), the system automatically provisions an official Student Number (`YYYY-XXXXXX`), an institutional TTU email (`firstname.lastname@ttu.edu.ph`), and dispatches welcome credentials via email.
- **Rule ADM-05 (Document Requirement Constraints):** Online applicants must submit all mandatory documents (Report Card or Transcript of Records, Certificate of Good Moral Character, PSA Birth Certificate, 2x2 ID Picture). All submitted documents must be marked `verified` before an application can be approved.

---

## 3. Curriculum & Scheduling Rules
- **Rule CURR-01 (Curriculum Locking):** An application is locked to a specific curriculum version ID upon entry and permanently attached to the user record for college programs.
- **Rule CURR-02 (Curriculum Immutability):** Active curricula with enrolled students cannot be destructively edited or deleted.
- **Rule SCHED-01 (Section Capacity):** Students cannot enroll into sections that have reached maximum capacity.
- **Rule SCHED-02 (Irregular Student Validation):** Irregular students selecting custom subject schedules must resolve schedule/time overlaps and capacity limits via `Schedule::validateSelectedSubjects()`.

---

## 4. Finance & Assessment Rules
- **Rule FIN-01 (Tuition Rate per Unit):** When `fee_templates.is_per_unit = 1`, total tuition is evaluated dynamically as:
  $$\text{Total Tuition} = \text{Total Enrolled Units} \times \text{Template Tuition Rate}$$
- **Rule FIN-02 (Scholarship Priority):** Approved scholarship discounts (fixed or percentage) are deducted from the gross assessment *before* calculating the net balance payable.
- **Rule FIN-03 (Payment Verification):** Bank transfer payment proofs uploaded by applicants remain in `pending` state until manually verified by the Cashier.
- **Rule FIN-04 (Minimum Downpayment for Final Enrollment):** Any payment recorded or verified by the Cashier that brings `total_paid >= ₱3,000.00` (or covers the full balance) automatically triggers `finalizeStudentEnrollment()`, transitioning the application to `status = 'enrolled'`.

---

## 5. Input Validation & Demographic Rules
- **Rule VAL-01 (Learner Reference Number):** If provided, the LRN must strictly be a 12-digit numeric string (`/^\d{12}$/`).
- **Rule VAL-02 (Philippine Mobile Contact):** Mobile numbers must be valid 11-digit numbers starting with `09` (or `+639\d{9}`).
- **Rule VAL-03 (Password Complexity):** Passwords must contain a minimum of 8 characters, at least one uppercase letter, one lowercase letter, one number, and one special character (`isPasswordStrong()`).

---

## 6. LMS Academic Rules
- **Rule LMS-01 (Parasitic Access):** A student can only access an LMS course if they have an active enrollment record in `college_enrollments` or `shs_enrollments` linked to `status = 'enrolled'`.
- **Rule LMS-02 (Real-Time Course Sync):** Adding or dropping a subject in the Registrar catalog immediately updates the student's LMS course roster.
- **Rule LMS-03 (Secure File Access):** Course materials and assignment submissions cannot be downloaded directly via static URLs; all downloads must pass through authenticated permission controllers.

---
**Related:**
- [[Student Lifecycle Workflow]]
- [[Payment & Assessment Workflow]]
- [[Authentication & Email Verification]]
