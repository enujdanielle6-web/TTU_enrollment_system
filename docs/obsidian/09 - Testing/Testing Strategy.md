# Testing Strategy

This document outlines the testing methodology, critical verification test cases, and quality assurance procedures for the TTU Enrollment System and LMS.

---

## 1. Testing Framework & Philosophy
Due to the system's **Hybrid MVC** architecture and raw PDO data access in controllers:
- **Core Strategy:** Comprehensive **Integration & Manual Functional Testing** supplemented with automated scenario test suites located in `/scratch` and `/scripts`.
- **Target Automated Approach:** End-to-End browser test automation combined with standalone scenario-based CLI test suites verifying state machine transitions, foreign key constraints, and multi-tenant security boundaries.

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
| **TC-10** | **College Curriculum Versioning & Immutability** | College programs in database. | Execute 24 lifecycle audit scenarios (`scratch/audit_curriculum_system.php`). | Drafts are fully editable/reorderable; Active/Archived curricula reject structural edits/deletes; Clone-to-draft produces Version $N+1$. | **PASSED (24/24)** |
| **TC-11** | **SHS Curriculum Versioning & Immutability** | SHS strands in database. | Execute 24 lifecycle audit scenarios (`scratch/audit_shs_curriculum_system.php`). | SHS follows identical Draft $\rightarrow$ Active $\rightarrow$ Archived lifecycle with immutable active locks and version branching. | **PASSED (24/24)** |
| **TC-12** | **Subject Catalog Immutability & Safety** | Shared universal subjects table. | Execute 15 safety checks (`scratch/verify_subject_immutability.php`). | `ON DELETE RESTRICT` blocks direct deletion of referenced subjects; locked structural fields reject edits; non-destructive `status` toggling functional. | **PASSED (15/15)** |
| **TC-13** | **Scheduler Visual Timetable & Conflict Engine** | Sections and subjects configured. | Build section schedule via `/admin/scheduler/schedule_builder.php`. | Room and instructor collisions detected; delivery modes (`Face-to-Face`/`Online`) persisted; timetable renders without SPA script collision. | **PASSED** |

---

## 3. Automated Test Suites & CLI Scripts

- **`scratch/audit_curriculum_system.php`**: Comprehensive 24-scenario College curriculum lifecycle and immutability test suite.
- **`scratch/audit_shs_curriculum_system.php`**: Comprehensive 24-scenario SHS curriculum lifecycle and immutability test suite.
- **`scratch/verify_subject_immutability.php`**: 15-check automated verification suite for `ON DELETE RESTRICT` constraints, usage detection metrics, field mutation locking, and financial snapshot protection.
- **`scratch/test_builder_scheduler_role.php` & `scratch/test_schedule_process.php`**: Verification scripts for timetable generation, room/faculty conflict checking, and payload processing.

---
**Related:**
- [[Coding Standards]]
- [[Development Guide]]
- [[Curriculum Architecture]]
- [[Subject Catalog Immutability Architecture]]
- [[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]
