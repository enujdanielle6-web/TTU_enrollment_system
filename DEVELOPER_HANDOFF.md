# Developer Handoff: Recent Architecture & Bug Fixes
*Date: August 18, 2026*

This document outlines the recent changes made to the system architecture and bug fixes applied to the administrative modules. Please review these changes before continuing development.

---

## 1. Finance Module: "Tuition Rate per Unit" Refactoring
The "Tuition Fee" logic has been decoupled from a static fixed amount and transitioned into a dynamic, unit-based multiplier while preserving backward compatibility for older fee templates.

### Database Changes
- **Table:** `fee_templates`
- **Addition:** Added a new `is_per_unit` (TINYINT(1), DEFAULT 0) column to distinguish between old fixed-tuition templates and new unit-rate templates.
- **Schema Updated:** `schema_dump.sql` has been freshly updated via `mysqldump` to reflect this change natively.

### Controller & Logic Changes
- **`FeeController.php`**: Now hardcodes `is_per_unit = 1` for newly created or updated fee templates. The total static amount calculation (Misc + Reg + Lab + Other) was explicitly adjusted to exclude the tuition rate, preventing massive inaccuracies on the frontend.
- **`AdmissionsController.php`**: The tuition assessment logic was rewritten. The system now fetches total enrolled units (via `college_enrollments` or `shs_enrollments` joined with `subjects`) and multiplies that by the template's `tuition_fee` (which now acts as a *rate*) **IF** `is_per_unit` is true. Otherwise, it safely falls back to evaluating `tuition_fee` as a static total.
- **`ApplicantController.php` & `FinanceController.php`**: Updated the core assessment query to `LEFT JOIN fee_templates` to fetch the `is_per_unit` flag, and expanded the `enrolledSubjects` fetching logic to natively support Senior High School (SHS) students, not just College students.

### UI & View Changes
- **`fees.php`**: Relabeled all "Tuition Fee" input fields to "Tuition Rate per Unit" with a clear `/ unit` suffix in both the Add and Edit modals.
- **`assessment.php` & `cashier_assessment.php`**: Updated the financial breakdown logic. If a student is using an `is_per_unit` template, the view explicitly details the math (e.g., `18 units @ ₱500.00/unit`). 

---

## 2. Registrar Module: Global Subjects Bug Fix
- **File Fixed:** `subjects.php`
- **Issue:** Opening the "Edit Subject" modal was causing the screen to turn black and freeze.
- **Fix:** The HTML structure was invalid because the `<!-- Edit Subject Modal -->` loop was incorrectly placed *inside* the `<tbody>` tag. Bootstrap's backdrop layer cannot calculate z-index correctly inside nested table bodies. The modal generation loop was extracted and moved entirely outside of the `<table>` and placed at the bottom of the document hierarchy.

---

## 3. Scholarship Module: Routing & Redirect Fixes
- **Missing Route Fixed:** Navigating to "Active Scholars" threw a 404. Added `$router->get('/admin/scholarship/scholars.php', ...)` directly into `web.php` to properly map the sidebar link to the `ScholarshipController@scholars` method.
- **Inconsistent Redirect Fixed:** Inside `ScholarshipController.php`, the form handler for processing applications was using a raw PHP `header('Location: ...')` which bypassed the central routing infrastructure. This was rewritten to use the framework's native `$response->redirect(...)` to prevent blank screens or routing loop errors post-submission.

---

## 4. Environment Cleanup
- A temporary SQL migration script that was used to test the `fee_templates` database injection has been permanently deleted.
- The root `schema_dump.sql` was completely overwritten with a clean `--no-data` export directly from the live MySQL database, ensuring the schema file is an exact 1-to-1 reflection of production.
