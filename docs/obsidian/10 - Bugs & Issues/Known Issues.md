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

## 2. Active Technical Debt & Planned Improvements
1. **Fat Controller Complexity:** While `BaseModel`, `CollegeEnrollmentRepository`, and `LmsService` have reduced duplication, administrative controllers still handle multi-faceted responsibilities. Gradual service extraction is planned.
2. **Automated E2E Test Suite:** Implement continuous CI test runners to validate multi-step enrollment workflows.

---
**Related:**
- [[System Architecture]]
- [[LMS Navigation and Render Bugs Fixed]]
- [[Development Guide]]
