# Session Report: Automated Enrollment Bots, Scholarships & Multi-Section LMS Isolation

**Date**: September 13, 2026  
**Session Focus**: Full End-to-End Enrollment Automation, Admissions UI/UX Modernization, Public Scholarship Integration, Irregular Student Preservation, and LMS Multi-Section Instance Isolation  
**Lead Agents**: Chief Software Architect, QA Engineer, Database Engineer, PHP Backend Engineer, Frontend UI/UX Designer, Documentation Writer  

---

## 1. Executive Summary

This session successfully achieved complete automation, verification, and UI modernization of the TTU Enrollment System across all primary student personas:
1. **Regular College Student Lifecycle Automation:** Built and executed `scripts/test_enrollment_bot.php` covering the full enrollment pipeline through to LMS access with 100% pass.
2. **Senior High School Grade 12 STEM Lifecycle Automation:** Built and executed `scripts/test_shs_enrollment_bot.php` for Grade 12 STEM students, validating curriculum selection, SHS fee assessments, and SHS LMS courses.
3. **Irregular College Student with Scholarship Automation:** Built and executed `scripts/test_irregular_scholarship_bot.php`, resolving custom subject preservation during matriculation and verifying 100% academic scholarship deductions.
4. **Multi-Section LMS Course Instance Isolation:** Built and executed `scripts/test_two_sections_lms.php`, proving that identical courses across different sections (`BSIT 1-A` and `BSIT 1-B`) maintain distinct `lms_courses` instances with zero duplication, isolated content, and strict authorization boundaries.
5. **Admissions & Registrar UI/UX Upgrades:** Modernized the Admissions dashboard, review table, and application detail views with responsive glassmorphic cards and interactive SweetAlert2 confirmation modals in Subjects management.
6. **Public Scholarship Showcase:** Added dynamic scholarship program cards on the university landing page (`home.php`) and navbar integration.

---

## 2. Key Architecture & Code Changes

### 2.1 Backend Domain Services & Controllers
- **`app/Services/EnrollmentService.php`**:
  - Enhanced `finalizeEnrollment()` to inspect `application_subject_requests` before assigning regular section subjects. When approved custom subjects exist for an irregular student, it enrolls the student into their exact requested subjects, preventing accidental section subject overwrites.
  - Fixed parameter signature default in `sendStudentCredentialsEmail()` (`$tempPassword = ''`) to prevent `TypeError` during automated dispatch.
- **`app/Services/AssessmentService.php`**:
  - Updated `calculateAssessment()` and `snapshotAssessmentItems()` to query `application_subject_requests` for irregular applicants. Billed units and frozen line items in `assessment_items` now accurately reflect custom irregular schedules.
  - Dynamic scholarship deduction calculation verifies percentage and fixed-amount grants against tuition fees.
- **`app/Controllers/HomeController.php` & `app/Views/home.php`**:
  - Dynamically queries active scholarships (`SELECT * FROM scholarships WHERE is_active = 1`) and passes them to the landing page.
  - Implemented the `#scholarships` showcase section featuring gradient badge accents, coverage highlights (100% Tuition / Monthly Stipend), requirement modal triggers, and direct application routes.
- **`app/Controllers/Admin/Admissions/AdmissionsController.php`**:
  - Added `doc_count` correlated subquery to `review()` so application cards accurately display total uploaded documents alongside pending counts.

### 2.2 UI/UX & Frontend Upgrades
- **`app/Views/admin/admissions/dashboard.php`**:
  - Premium stat cards with live intake statistics, pending clearance counters, quick-filter status pills, and interactive application tables.
- **`app/Views/admin/admissions/review.php`**:
  - Modern review table with filter buttons, document badges, medical clearance indicators, and batch processing hooks.
- **`app/Views/admin/admissions/detail.php`**:
  - High-clarity applicant dossier layout featuring an irregular subject review drawer, document inspector, medical clearance badge, and action modals.
- **`app/Views/admin/registrar/subjects.php`**:
  - Replaced native browser `alert()` and `confirm()` dialogs with custom interactive SweetAlert2 dialogs (`confirmInactivate`, `confirmActivate`), warning administrators about active curriculum or section references before toggling status.
- **`css/main.css`**:
  - Modern utility classes, CSS custom properties, glassmorphism containers, animated badge pulses, and responsive layout polish.

---

## 3. Database Schema Updates

1. **`shs_curriculum_subjects`**:
   - Added column `display_order INT NOT NULL DEFAULT 1` to support structured ordering in Senior High School curriculum definitions.
2. **`college_sections` & `lms_courses` (Multi-Section Data)**:
   - Added Section `BSIT 1-B` (ID 3) linked to curriculum 1.
   - Configured `college_section_subjects` for Section `BSIT 1-B` covering `CC101`, `CC102`, and `ENG101`.
   - Seeded distinct `lms_courses` instances:
     - Section `BSIT 1-A`: Course IDs `1, 2, 3` (`CC101-BSIT1A`, `CC102-BSIT1A`, `ENG101-BSIT1A`)
     - Section `BSIT 1-B`: Course IDs `11, 12, 13` (`CC101-BSIT1B`, `CC102-BSIT1B`, `ENG101-BSIT1B`)

---

## 4. Verification & Automated Test Suites

| Suite / Script | Target Lifecycle Scenario | Key Assertions Verified | Status |
|---|---|---|---|
| **`scripts/test_enrollment_bot.php`** | College Regular BSIT | OTP, Documents, Clinic clearance, ₱13,350 assessment, Cashier receipt, Student ID `2026-000005`, 6 LMS courses. | **100% PASS** |
| **`scripts/test_shs_enrollment_bot.php`** | SHS Regular Grade 12 STEM | Grade 12 curriculum mapping, Clinic clearance, SHS tuition assessment, Cashier receipt, Student ID `2026-000006`, Grade 12 LMS courses. | **100% PASS** |
| **`scripts/test_irregular_scholarship_bot.php`** | Irregular College + 100% Scholarship | 3 custom requested subjects (9 units), 100% tuition waiver (₱4,500 discount), ₱3,850 net cashier payment, non-overwritten subjects in `college_enrollments`, Student ID `2026-000007`, LMS courses match requested subjects. | **100% PASS** |
| **`scripts/test_two_sections_lms.php`** | LMS Multi-Section Isolation (`BSIT 1-A` vs `BSIT 1-B`) | Student A (`2026-000010`) $\rightarrow$ Course IDs `1, 2, 3`; Student B (`2026-000011`) $\rightarrow$ Course IDs `11, 12, 13`; 0 duplicate cards, strict cross-section course access blocking, isolated module content. | **100% PASS** |

---

## 5. Artifacts & Documentation Updates

- **ADR-011**: Created [[ADR-011 Multi-Section LMS Subject Instance Isolation and Irregular Student Subject Preservation]].
- **ADR Index**: Updated [[ADR Index]].
- **Testing Strategy**: Added `TC-14` through `TC-17` and documented permanent `/scripts` test bots in [[Testing Strategy]].
- **Module Specs**:
  - Updated [[Admissions]]: Documented UI upgrades and irregular subject review.
  - Updated [[Scholarship]]: Documented landing page showcase and assessment discount math.
  - Updated [[Landing Page & Program Card Customization]]: Documented `#scholarships` showcase section.
  - Updated [[LMS]]: Documented multi-section course instance isolation.
- **Database Reference**:
  - Updated `shs_curriculum_subjects` and `lms_courses` documentation in [[Data Dictionary]].
- **Services Reference**:
  - Updated `AssessmentService` and `EnrollmentService` in [[03 - Services Reference]].

---
**Related:**
- [[Reports Overview]]
- [[Testing Strategy]]
- [[ADR Index]]
- [[System Architecture]]
