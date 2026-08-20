# Applicant Portal

**Path**: `applicant/`  
**Required Role**: `applicant`  
**Controllers**: [`ApplicantController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php), [`EnrollController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/EnrollController.php), [`DocumentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/DocumentController.php), [`HealthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/HealthController.php)

The Applicant Portal is the dedicated self-service interface for prospective and newly admitted students.

---

## 1. Onboarding & Registration Flow
1. **Public Registration:** User registers on `/sia/auth/register.php`.
2. **6-Digit Email OTP Verification:** User must enter the OTP code dispatched to their email before gaining access to the dashboard.
3. **Application Submission:** User completes personal, educational background, guardian information, and selects an academic program (College) or strand (SHS).
4. **Requirements Upload:** Submits digital scans of required admission documents (PSA, Form 137/138, Good Moral).
5. **Health Information Submission:** Submits medical history and emergency contacts via `/applicant/health_info.php`.
6. **Enrollment & Section Selection:** Once approved by Admissions and cleared by Clinic, selects timetable sections.
7. **Assessment & Payment:** Views fee assessment and uploads payment proof.
8. **Confirmation & Progress:** Tracks application progress to 100% completion.

---

## 2. Core Endpoints & Views
| Endpoint | Method | Controller & Action | Description |
|---|---|---|---|
| `/applicant/dashboard.php` | GET | `ApplicantController@dashboard` | Master dashboard displaying status tracker, next step action banners, and progress bar. |
| `/applicant/application_form.php` | GET | `ApplicantController@applicationForm` | Comprehensive admission application form. |
| `/applicant/application_process.php` | POST | `ApplicantController@processApplication` | Processes application submission and updates database. |
| `/applicant/requirements.php` | GET | `ApplicantController@requirements` | Document requirement upload interface. |
| `/applicant/upload_document.php` | POST | `ApplicantController@uploadDocument` | Handles secure document file upload to `/uploads/documents/`. |
| `/applicant/health_info.php` | GET | `HealthController@index` | Medical background and health declaration form. |
| `/applicant/health_process.php` | POST | `HealthController@process` | Saves `health_records` and routes to next workflow step. |
| `/applicant/enroll.php` | GET | `EnrollController@showForm` | Subject schedule builder and section selection. |
| `/applicant/enroll_process.php` | POST | `EnrollController@processForm` | Submits enrolled subjects into `college_enrollments` / `shs_enrollments`. |
| `/applicant/assessment.php` | GET | `ApplicantController@assessment` | Financial assessment statement with dynamic unit breakdown. |
| `/applicant/payment_process.php` | POST | `ApplicantController@processPayment` | Uploads proof of payment to `/uploads/payments/`. |
| `/applicant/print_slip.php` | GET | `ApplicantController@printSlip` | Printable Certificate of Matriculation / Enrollment Assessment Slip. |
| `/applicant/scholarships.php` | GET | `ApplicantController@scholarships` | Available scholarships catalog and application form. |

---

## 3. Business & Gating Rules
- **OTP Verification Gate:** Unverified users cannot enter the applicant dashboard.
- **Sequential Progression:**
  - Cannot access enrollment until Admissions status is `approved`.
  - Must submit Health Information (`health_records`) post-approval before selecting class schedules.
  - Cannot finalize enrollment without valid fee template assessment.
- **100% Progress Calculation:** Dynamically evaluates completed stages (Application + Docs + Health + Enrollment + Payment + LMS Credentials).

---
**Related:**
- [[Applicant Registration Workflow]]
- [[Health Submission & Clearance Workflow]]
- [[Payment & Assessment Workflow]]
- [[Authentication & Email Verification]]
