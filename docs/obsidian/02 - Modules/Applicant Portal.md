# Applicant Portal

## Purpose
The primary interface for prospective and current students to interact with the enrollment system.

## Users
- Role: `applicant`

## Main Pages & Entry Points
- `GET /applicant/dashboard.php` - The main landing area.
- `GET /applicant/application_form.php` - The form to submit demographic and academic history.
- `GET /applicant/requirements.php` - For uploading documents.
- `GET /applicant/enroll.php` - For selecting subjects and sections.

## Related Files
- **Controller:** `app/Controllers/ApplicantController.php`, `EnrollController.php`
- **Views:** `app/Views/applicant/*`

## Workflow
1. User logs in.
2. If `applications` table has no active row, forces them to fill `application_form.php`.
3. If pending review, shows status.
4. If approved, allows access to `enroll.php`.

## Known Issues
- Fat controller logic within `ApplicantController.php` directly executes large SQL queries rather than using models.
- "Applicant" role is used even after a student is fully enrolled.

## Related
- [[Applicant Registration Workflow]]
- [[Users Table]]
- [[Applications Table]]
