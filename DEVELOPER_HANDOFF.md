# Developer Handoff: System Architecture, Workflows & Recent Implementations
*Last Updated: August 20, 2026*

This document summarizes the current state of the TTU Enrollment System and LMS architecture, recent feature releases, schema migrations, and critical developer guidelines.

---

## 1. Authentication & Email OTP Verification System
Newly registered applicants must verify their email before accessing the portal.

### Implementation Details
- **Tables Modified:** `users` table expanded with `email_verified` (TINYINT(1) DEFAULT 1), `verification_code` (VARCHAR(10) NULL), and `verification_expires_at` (DATETIME NULL). Existing users default to `email_verified = 1`.
- **OTP Generation & Expiry:** 6-digit cryptographic random code (`random_int(100000, 999999)`), valid for **15 minutes**.
- **Email Delivery Helper:** [`sendVerificationCodeEmail()`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php) uses PHPMailer with embedded university logo and campus hero image. Automatically loads `.env` SMTP variables.
- **Verification UI:** [`app/Views/auth/verify_email.php`](file:///c:/xampp/htdocs/sia/app/Views/auth/verify_email.php) features 6 auto-tabbing input boxes, clipboard paste support, 60s resend cooldown timer, and error/warning feedback.
- **Authentication Gating:** Unverified applicants attempting login via `/sia/auth/login.php` are intercepted, issued a fresh OTP, and redirected to `/sia/auth/verify_email.php`.
- **LMS Logout Routing:** Dedicated endpoints (`/sia/auth/lms_student_logout.php`, `/sia/auth/lms_faculty_logout.php`) redirect students and faculty directly back to their respective LMS login portals.

---

## 2. Dynamic LMS & Dual Enrollment Engine (College + SHS)
Replaced static dummy LMS courses with dynamic auto-provisioning across both academic tiers.

### Implementation Details
- **Repositories:**
  - [`CollegeEnrollmentRepository.php`](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php): Queries active enrolled subjects from `college_enrollments` linked to `applications.status = 'enrolled'`. Auto-provisions `lms_courses` records.
  - [`ShsEnrollmentRepository.php`](file:///c:/xampp/htdocs/sia/app/Repositories/ShsEnrollmentRepository.php): Queries active enrolled subjects from `shs_enrollments` and maps adviser/instructor details.
- **LMS Service Layer:** [`app/Services/LmsService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) provides:
  - `getStudentUpcomingDeadlines()`: Live assignments and quizzes from `lms_assignments` and `lms_quizzes`.
  - `getStudentAnnouncements()`: Course and university announcements.
  - `getStudentNextEvent()`: Computes next schedule block.
  - `getStudentStreak()`: Calculates real student activity streak.
- **Student LMS Login:** Students log in via their **Student Number** (e.g., `2026-000003`) and their unified account password.

---

## 3. Automated Student Credentials & Email Dispatch
Upon finalizing enrollment in Admissions:
- **Trigger:** [`AdmissionsController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Admissions/AdmissionsController.php) updates application to `enrolled`.
- **Generation:** Generates official Student Number (`YYYY-XXXXXX`) and institutional TTU email (`firstname.lastname@ttu.edu.ph`).
- **Dispatch:** Calls [`sendStudentCredentialsEmail()`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php) to send a branded welcome email with temporary LMS/Portal credentials.

---

## 4. Finance Module: "Tuition Rate per Unit" Refactoring
- **Table:** `fee_templates` (`is_per_unit` TINYINT(1) DEFAULT 0).
- **Assessment Math:** `Total Tuition = Enrolled Units × tuition_fee (rate) + Misc Fees`.
- **UI:** Assessment breakdown displays unit calculation (e.g. `18 units @ ₱500.00/unit`) and supports bank payment proof upload with cashier verification.

---

## 5. Health Information & Clinic Clearance Subsystem
- **Table:** `health_records` (linked to `applications.id` and `users.id`).
- **Validation Rule:** Post-approval, an applicant must submit their Health Information via `/applicant/health_info.php` before proceeding to subject enrollment and assessment.
- **Admin Verification:** Reviewed by the Clinic staff via `/admin/clinic/clinic_dashboard.php` (`ClinicController`).

---

## 6. Environment & Schema Reference
- **Active Schema Dump:** [`schema_dump.sql`](file:///c:/xampp/htdocs/sia/schema_dump.sql) contains the complete 41-table structure.
- **Configuration:** Copy `.env.example` or edit `.env` for database and Google SMTP credentials.
