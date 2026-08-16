# Admissions Module

**Path**: `admin/admissions/`
**Role Required**: `admissions` or `superadmin`

The Admissions module is the first administrative checkpoint in the [[Enrollment Workflow]]. 

## Responsibilities
- Review newly submitted applications (`status: pending`).
- Inspect applicant-uploaded documents (Birth Certificates, Good Moral certificates, Report Cards) via `application_process.php`.
- View applicant details.
- Approve or Reject the application.

## Core Files
- `admissions_dashboard.php`: The main datatable showing pending and processed applications.
- `application_process.php`: The detail view where an officer can accept/reject documents and change the application status.

## Data Flow
When Admissions approves an application, it does NOT immediately go to Finance. It must also wait for the [[Modules/Clinic]] to approve the medical records. Only when *both* are cleared does the application become eligible for [[Modules/Finance]] assessment.
