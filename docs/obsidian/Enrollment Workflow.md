# The Enrollment Workflow

This document outlines the strict lifecycle an applicant goes through to become a fully enrolled student at TTU. The status of this lifecycle is tracked in the `applications.status` column, orchestrated by our MVC Controllers.

## Stage 1: Registration (`status: pending`)
1. A new user registers via `/auth/register.php` (Handled by `AuthController@registerProcess`).
2. The user logs in and submits an enrollment form via `/applicant/enroll.php` (Handled by `EnrollController@processForm`).
3. This creates a record in the `applications` table.
4. The user uploads required academic documents via `/applicant/documents.php` (Handled by `DocumentController@upload`).

## Stage 2: Verification (`status: pending`)
The application is now simultaneously visible to two different departments:
- **[[Modules/Admissions]]**: Reviews the uploaded documents via `/admin/admissions/review.php` (`AdmissionsController@review`).
- **[[Modules/Clinic]]**: Reviews the submitted medical forms/X-rays via `/admin/clinic/medical_clearance.php` (`ClinicController@index`).

*Both departments must approve their respective requirements before the application can proceed.*

## Stage 3: Assessment (`status: assessed`)
1. Once Admissions and Clinic clear the applicant, the application moves to the Finance queue.
2. **[[Modules/Finance]]** generates an Assessment via `/admin/finance/cashier_assessment.php` (`FinanceController@assessment`).
3. The assessment calculates total fees based on the `fee_templates` matching the applicant's chosen program.
4. If the applicant applied for a scholarship via **[[Modules/Scholarship]]** and was approved (`ScholarshipController`), the discount is applied to the assessment here.
5. The application status changes to `assessed`.

## Stage 4: Payment (`status: assessed`)
1. The applicant sees their bill on their dashboard (`ApplicantController@dashboard`).
2. The applicant must pay at least a minimum downpayment (e.g., ₱3,000).
3. Payments are recorded by the Cashier via `/admin/finance/cashier_payments.php` (`FinanceController@payments`).
4. Once the minimum payment is met, the applicant is cleared for final enrollment.

## Stage 5: Enrollment (`status: enrolled`)
1. The application appears in the **[[Modules/Registrar]]** queue (`/admin/registrar/college_enrollment_queue.php` or `shs_enrollment_queue.php` via `RegistrarController`).
2. The Registrar reviews the applicant, assigns them to a specific Section (`college_sections` or `shs_sections`), and clicks "Finalize Enrollment".
3. This triggers a database insertion into `college_enrollments` or `shs_enrollments`.
4. The application status is changed to `enrolled`.
5. The applicant can now view their official class schedule (generated via the Curriculum Builder) on their dashboard.

## Edge Cases
- **Rejection (`status: rejected`)**: If Admissions finds fraudulent documents, they can reject the application. The applicant must start over.
- **Withdrawal**: If a student drops out, they must be manually removed from the database by a System Admin.
