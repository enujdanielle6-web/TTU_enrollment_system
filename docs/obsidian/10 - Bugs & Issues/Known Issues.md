# Known Issues & Resolution Log

This document tracks architectural debt, bugs discovered during development, and their verified resolutions.

---

## 1. Resolved Issues Log

### Issue 1: Environment Variables Not Loading in Front Controller
- **Module:** [[Architecture]] / [[Security Overview]]
- **Symptoms:** SMTP authentication failed during applicant registration and email dispatch because `getenv('SMTP_USERNAME')` returned empty strings.
- **Root Cause:** `public/index.php` was initialized without loading the `.env` file into PHP environment superglobals.
- **Resolution:** Added an automated `.env` parser directly into `public/index.php` and added fallback environment loading inside `app/Helpers/functions.php`.

### Issue 2: LMS Sign-out Incorrect Redirection
- **Module:** [[LMS]]
- **Symptoms:** Clicking "Sign out" inside the Student or Faculty LMS redirected users to the general Admissions portal login (`/sia/auth/login.php`).
- **Resolution:** Implemented dedicated endpoints (`/auth/lms_student_logout.php` and `/auth/lms_faculty_logout.php`) in `LmsAuthController` and updated `AuthController::logout` to detect LMS sessions.

### Issue 3: Senior High School (SHS) LMS Course Isolation
- **Module:** [[LMS]]
- **Symptoms:** LMS initially only queried `college_enrollments`, causing SHS students to see zero courses.
- **Resolution:** Created `ShsEnrollmentRepository` alongside `CollegeEnrollmentRepository` in `app/Repositories/`, enabling dynamic course auto-provisioning for both academic levels.

### Issue 4: Registrar Subject Edit Modal Freeze
- **Module:** [[Registrar]]
- **Symptoms:** Clicking "Edit Subject" caused the screen to black out and freeze.
- **Root Cause:** Modal HTML markup was nested inside a table `<tbody>` tag, breaking Bootstrap's z-index backdrop calculation.
- **Resolution:** Extracted modal loops outside the `<table>` element to the bottom of the view template.

### Issue 5: Missing Active Scholars Route
- **Module:** [[Scholarship]]
- **Symptoms:** Navigating to "Active Scholars" returned a 404 Not Found error.
- **Resolution:** Registered `$router->get('/admin/scholarship/scholars.php', ...)` in `app/Routes/web.php` mapping to `ScholarshipController@scholars`.

---

## 2. Active Technical Debt & Discovered Codebase Defects

### Issue 6: Health Information Form Undefined Variable Block
- **Module:** [[Applicant Portal]] / `HealthController.php`
- **Location:** `app/Controllers/HealthController.php:66-92`
- **Symptoms:** Applicant health form submissions always fail with "Please fill out all required physical and emergency contact fields."
- **Root Cause:** `$heightVal = (float)$heightRaw;` and `$weightVal = (float)$weightRaw;` are invoked without extracting `$heightRaw = $request->input('height')` and `$weightRaw = $request->input('weight')`.
- **Status:** Documented defect awaiting controller patch.

### Issue 7: Non-Existent `shs_curriculum` Table in SHS Controllers & API
- **Module:** [[Registrar]] / `ShsController.php` & `ApplicantApiController.php`
- **Location:** `app/Controllers/Admin/Registrar/ShsController.php:257,314` and `app/Controllers/Api/ApplicantApiController.php:48`
- **Symptoms:** Adding/removing subjects to SHS curriculum or querying SHS curriculum preview returns fatal `PDOException: Table 'sia.shs_curriculum' doesn't exist`.
- **Root Cause:** Queries reference obsolete table `shs_curriculum` instead of `shs_curricula` and `shs_curriculum_subjects`.
- **Status:** Documented defect awaiting controller patch.

### Issue 8: Undefined Function `showErrorPage()`
- **Module:** `ScholarshipController.php`, `RegistrarController.php`, `ReportController.php`
- **Location:** `ScholarshipController.php:125`, `RegistrarController.php:323,366`, `ReportController.php:126`
- **Symptoms:** Triggering database exception blocks causes fatal error `Call to undefined function showErrorPage()`.
- **Root Cause:** The helper function `showErrorPage()` was never implemented in `app/Helpers/functions.php`.
- **Status:** Documented defect awaiting helper definition.

### Issue 9: Erroneous Capacity Foreign Key Comparison in `Schedule.php`
- **Module:** [[Scheduler]] / `Schedule.php`
- **Location:** `app/Models/Schedule.php:52`
- **Symptoms:** Irregular subject schedule capacity checks report inaccurate student counts.
- **Root Cause:** `$capStmt->execute([$off['id'], $off['subject_id']])` compares `shs_enrollments.shs_section_id` against `shs_section_subjects.id` (offering ID) instead of the actual `shs_section_id`.
- **Status:** Documented defect awaiting model patch.

### Issue 10: Setting Key Mismatch in Scholarship Recipient Enrollment
- **Module:** [[Scholarship]] / `ScholarshipController.php`
- **Location:** `app/Controllers/Admin/Scholarship/ScholarshipController.php:257-270`
- **Symptoms:** Approved scholarships are not saved to `scholarship_recipients` or recalculated in `student_assessments`.
- **Root Cause:** Controller queries `system_settings` for `active_academic_year_id`, but `SystemController.php` saves it as `active_school_year`.
- **Status:** Documented defect awaiting key alignment.

### Issue 11: Master Password Backdoor in LMS Student Login
- **Module:** [[LMS]] / `LmsAuthController.php`
- **Location:** `app/Controllers/Lms/LmsAuthController.php:76`
- **Symptoms:** Student accounts can be logged into using plaintext string `'password123'` or the student's plain student number without password verification.
- **Status:** Security risk documented for removal in subsequent security patch.

---
**Related:**
- [[System Architecture]]
- [[LMS Navigation and Render Bugs Fixed]]
- [[Development Guide]]
