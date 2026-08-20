# Clinic & Health Module

**Paths**: `admin/clinic/` (Admin) and `applicant/health_info.php` (Applicant)  
**Required Roles**: `clinic`, `admin`, `superadmin`  
**Controllers**: [`ClinicController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Clinic/ClinicController.php), [`HealthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/HealthController.php)  
**Database Table**: `health_records`

The Clinic Module manages student health declarations, medical clearances, and emergency medical information.

---

## 1. Responsibilities & Architecture
1. **Applicant Health Submission:** Prospective students submit health history, blood type, allergies, chronic medical conditions, medications, and emergency contacts via `/applicant/health_info.php`.
2. **Clinic Staff Evaluation:** The university clinic staff reviews submitted records via `/admin/clinic/medical_clearance.php`.
3. **Clearance Decision:** Records are marked as `pending`, `under_review`, `correction_required`, or `verified`.
4. **Enrollment Gating Rule:** After an applicant's academic application is approved by Admissions, the applicant **must submit their health information** before they can proceed to section selection and subject enrollment.

---

## 2. Core Endpoints & Actions
| Endpoint | Method | Controller & Action | Purpose |
|---|---|---|---|
| `/applicant/health_info.php` | GET | `HealthController@index` | Applicant form for submitting medical background and emergency info. |
| `/applicant/health_process.php` | POST | `HealthController@process` | Inserts or updates the student's row in `health_records`. |
| `/admin/clinic/clinic_dashboard.php` | GET | `ClinicController@dashboard` | Clinic metrics, verified counts, and pending review queue. |
| `/admin/clinic/medical_clearance.php` | GET | `ClinicController@index` | Full table of applicant health records with status filters. |
| `/admin/clinic/medical_detail.php` | GET | `ClinicController@detail` | Detailed view of a single applicant's health and emergency data. |
| `/admin/clinic/medical_process.php` | POST | `ClinicController@process` | Updates health verification status and admin remarks. |

---

## 3. Database Schema: `health_records`
- `id` (PK, int unsigned auto_increment)
- `user_id` (FK to `users.id`)
- `application_id` (FK to `applications.id`)
- `blood_type` (varchar 10)
- `allergies` (text)
- `medical_conditions` (text)
- `current_medications` (text)
- `emergency_contact_person` (varchar 100)
- `emergency_contact_relationship` (varchar 100)
- `emergency_contact_number` (varchar 50)
- `status` (`pending`, `under_review`, `correction_required`, `verified`)
- `admin_remarks` (text)
- `created_at` / `updated_at` (timestamp)

---
**Related:**
- [[Applicant Portal]]
- [[Health Submission & Clearance Workflow]]
- [[Student Lifecycle Workflow]]
