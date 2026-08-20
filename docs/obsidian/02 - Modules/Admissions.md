# Admissions Module

**Path**: `admin/admissions/`  
**Required Roles**: `admissions`, `admin`, `superadmin`  
**Controller**: [`AdmissionsController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Admissions/AdmissionsController.php)

The Admissions module is the primary administrative intake checkpoint in the [[Student Lifecycle Workflow]].

---

## 1. Core Responsibilities
1. **Application Intake & Review:** Reviews submitted applications across `pending`, `under_review`, and `correction_required` states.
2. **Document Verification:** Inspects applicant-uploaded admission requirements (PSA Birth Certificate, Form 137/138, Good Moral, 2x2 Photos) via `application_documents`.
3. **Application Decision:** Marks applications as `approved` or `rejected` with custom feedback and remarks.
4. **Final Enrollment & Credential Provisioning:** When approving final enrollment, automatically generates student numbers, creates institutional emails, and sends credentials.

---

## 2. Automated Student Credential Issuance
When an Admissions officer finalizes an application:
1. **Student Number Generation:** Generates a unique student ID formatted as `YYYY-XXXXXX` (e.g. `2026-000003`).
2. **Institutional TTU Email:** Formats and assigns a university email address (`first.last@ttu.edu.ph`).
3. **Password Generation:** Assigns a secure temporary password.
4. **Automated Dispatch:** Dispatches the branded HTML credentials email ([`welcome_credentials.php`](file:///c:/xampp/htdocs/sia/app/Views/emails/welcome_credentials.php)) via [`sendStudentCredentialsEmail()`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php) using PHPMailer.

---

## 3. Core Files & Endpoints
| Endpoint | Method | Action | Description |
|---|---|---|---|
| `/admin/admissions/admissions_dashboard.php` | GET | `index` | Summary statistics, pending queues, and application tables. |
| `/admin/admissions/review.php` | GET | `review` | Filterable list of applications by status and program. |
| `/admin/admissions/application_detail.php` | GET | `detail` | Complete applicant record, academic history, uploaded docs. |
| `/admin/admissions/application_process.php` | POST | `process` | Updates application status, document approval, notes, and triggers credentials. |
| `/admin/admissions/bulk_process.php` | POST | `bulkProcess` | Batch approvals/rejections of multiple applications. |
| `/admin/admissions/document_view.php` | GET | `viewDocument` | Secure document inspector with zoom preview. |

---

## 4. Integration & Data Flow
```mermaid
flowchart TD
    Applicant[Applicant Submits Requirements] --> Pending[Status: Pending in Admissions]
    Pending --> AdminReview[Admissions Officer Inspects Docs]
    AdminReview -->|Approved| Approved[Status: Approved]
    Approved --> HealthGate{Health Record Submitted?}
    HealthGate -->|Yes| ClinicReview[Clinic Clearance]
    HealthGate -->|No| ForceHealth[Applicant Prompted for Health Info]
    ClinicReview --> Enrollment[Subject Enrollment & Section Selection]
    Enrollment --> Assessment[Finance Assessment & Payment]
    Assessment --> Finalize[Admissions Finalizes Enrollment]
    Finalize --> Creds[System Auto-Dispatches Student Credentials Email]
    Creds --> LMS[Student Access to LMS Activated]
```

---
**Related:**
- [[Applicant Portal]]
- [[Clinic]]
- [[Authentication & Email Verification]]
- [[Email & Notification System]]
