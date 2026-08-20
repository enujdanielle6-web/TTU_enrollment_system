# Testing Strategy

This document outlines the testing methodology, critical verification test cases, and quality assurance procedures for the TTU Enrollment System and LMS.

---

## 1. Testing Framework & Philosophy
Due to the system's **Hybrid MVC** architecture and raw PDO data access in controllers:
- **Core Strategy:** Comprehensive **Integration & Manual Functional Testing** supplemented with CLI regression test scripts located in `/scripts`.
- **Target Automated Approach:** End-to-End browser test automation using Playwright/Cypress combined with PHPUnit HTTP test runners targeting `App\Core\Router`.

---

## 2. Critical Regression Test Matrix

| # | Test Scenario | Preconditions | Action / Trigger | Expected Outcome | Verification Status |
|---|---|---|---|---|---|
| **TC-01** | **Applicant Registration & OTP** | Database connected, SMTP configured. | Submit form on `/auth/register.php`. | User created with `email_verified=0`, 6-digit OTP code generated and delivered via email. Redirected to `/auth/verify_email.php`. | **PASSED** |
| **TC-02** | **OTP Verification & Gating** | User on `/auth/verify_email.php`. | Enter correct 6-digit code. | `email_verified` updated to 1, code cleared, session authenticated, redirected to `/applicant/dashboard.php`. | **PASSED** |
| **TC-03** | **OTP Expiry & Resend** | Verification code > 15 mins old or invalid. | Enter expired or incorrect code. | Rejected with error notice. Clicking "Resend Code" issues a fresh OTP and resets cooldown timer. | **PASSED** |
| **TC-04** | **Post-Approval Health Gating** | Applicant application approved by Admissions. | Navigate to `/applicant/enroll.php` without submitting health info. | Intercepted and forced to submit health declaration via `/applicant/health_info.php`. | **PASSED** |
| **TC-05** | **Dynamic Tuition Calculation** | Enrolled in 18 units, `fee_templates.is_per_unit = 1` @ ₱500/unit. | Generate assessment statement. | Total tuition evaluates to exactly ₱9,000.00 + misc fees. Itemized math displayed on `/applicant/assessment.php`. | **PASSED** |
| **TC-06** | **Automated Credential Generation** | Application in Admissions queue. | Admissions officer clicks "Enroll Applicant". | Application set to `enrolled`, Student ID generated (`YYYY-XXXXXX`), institutional email created, credentials email dispatched to student. | **PASSED** |
| **TC-07** | **Dual LMS Auto-Provisioning** | Enrolled in College or SHS subjects. | Student logs into LMS with Student Number (`YYYY-XXXXXX`). | Courses dynamically populated from `college_enrollments` / `shs_enrollments`. Live deadlines, announcements, and study streak rendered. | **PASSED** |
| **TC-08** | **Dedicated LMS Logout** | Active Student/Faculty LMS session. | Click "Sign out" in LMS sidebar. | Session destroyed, redirected directly to `/auth/lms_student_login.php` or `/auth/lms_faculty_login.php`. | **PASSED** |
| **TC-09** | **Role Route Isolation** | Logged in as `applicant`. | Manually access `/admin/admissions/admissions_dashboard.php`. | `RoleMiddleware` intercepts request and returns 403 Forbidden / redirects to login. | **PASSED** |

---

## 3. Test Utilities & CLI Scripts
Located in `/scripts`:
- Syntax linting: `php -l <filename>`
- Email dispatch validation: Verification and credentials transmission test scripts.

---
**Related:**
- [[Coding Standards]]
- [[Development Guide]]
- [[Business Rules]]
