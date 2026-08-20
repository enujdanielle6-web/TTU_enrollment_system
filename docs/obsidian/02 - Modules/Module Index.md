# Module Index

The TTU system is organized into modular administrative, applicant, and academic subsystems.

---

## 1. [[Applicant Portal]]
* **Purpose:** Prospective and returning student onboarding, online registration, requirements uploading, health data submission, self-enrollment, and assessment viewing.
* **Authorized Roles:** `applicant`
* **Controllers:** [`ApplicantController`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php), [`EnrollController`](file:///c:/xampp/htdocs/sia/app/Controllers/EnrollController.php), [`DocumentController`](file:///c:/xampp/htdocs/sia/app/Controllers/DocumentController.php), [`HealthController`](file:///c:/xampp/htdocs/sia/app/Controllers/HealthController.php)
* **Key Routes:** `/applicant/dashboard.php`, `/applicant/application_form.php`, `/applicant/health_info.php`, `/applicant/enroll.php`, `/applicant/assessment.php`

## 2. [[Admissions]]
* **Purpose:** Intake processing, document review, acceptance/rejection, and automated student number / institutional email credential generation.
* **Authorized Roles:** `admissions`, `admin`, `superadmin`
* **Controllers:** [`AdmissionsController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Admissions/AdmissionsController.php)
* **Key Routes:** `/admin/admissions/admissions_dashboard.php`, `/admin/admissions/review.php`, `/admin/admissions/application_detail.php`, `/admin/admissions/application_process.php`

## 3. [[Clinic]]
* **Purpose:** Review and verification of student health records, identification of medical conditions, emergency contact verification, and medical clearance.
* **Authorized Roles:** `clinic`, `admin`, `superadmin`
* **Controllers:** [`ClinicController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Clinic/ClinicController.php)
* **Key Routes:** `/admin/clinic/clinic_dashboard.php`, `/admin/clinic/medical_clearance.php`, `/admin/clinic/medical_detail.php`, `/admin/clinic/medical_process.php`

## 4. [[Registrar]]
* **Purpose:** Academic catalog management, curriculum builder (College & SHS), versioning, subject registry, and student masterlist records.
* **Authorized Roles:** `admin`, `superadmin`
* **Controllers:** [`RegistrarController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/RegistrarController.php), [`SubjectController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/SubjectController.php), [`CollegeController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/CollegeController.php), [`ShsController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Registrar/ShsController.php)
* **Key Routes:** `/admin/registrar/registrar_dashboard.php`, `/admin/registrar/subjects.php`, `/admin/registrar/college_curriculum_builder.php`, `/admin/registrar/shs_curriculum_builder.php`

## 5. [[Scheduler]]
* **Purpose:** Class section creation, subject timetables, room assignments, capacity limits, and faculty adviser assignments.
* **Authorized Roles:** `scheduler`, `admin`, `superadmin`
* **Controllers:** [`SchedulerController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scheduler/SchedulerController.php)
* **Key Routes:** `/admin/scheduler/scheduler_dashboard.php`, `/admin/scheduler/college_sections.php`, `/admin/scheduler/shs_sections.php`, `/admin/scheduler/schedule_builder.php`

## 6. [[Finance]]
* **Purpose:** Fee templates management, dynamic tuition rate-per-unit calculations, cashier payment processing, bank proof verification, and receipt issuance.
* **Authorized Roles:** `cashier`, `admin`, `superadmin`
* **Controllers:** [`FinanceController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php), [`FeeController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FeeController.php)
* **Key Routes:** `/admin/finance/cashier_dashboard.php`, `/admin/finance/cashier_assessment.php`, `/admin/finance/cashier_payments.php`, `/admin/finance/fees.php`

## 7. [[Scholarship]]
* **Purpose:** Financial aid program creation, application evaluation, grant approval, and tuition discount deduction.
* **Authorized Roles:** `scholarship`, `admin`, `superadmin`
* **Controllers:** [`ScholarshipController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scholarship/ScholarshipController.php)
* **Key Routes:** `/admin/scholarship/scholarship_dashboard.php`, `/admin/scholarship/scholarships.php`, `/admin/scholarship/scholars.php`, `/admin/scholarship/scholarship_review.php`

## 8. [[LMS]]
* **Purpose:** Full Learning Management System supporting both College and SHS courses, modules, assignments, timed quizzes, student gradebook, attendance, and announcements.
* **Authorized Roles:** `applicant` (enrolled students), `faculty`, `admin`
* **Controllers:** [`StudentController`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php), [`FacultyController`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php), [`LmsAuthController`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php), Assignment/Quiz/Gradebook/Attendance controllers.
* **Key Routes:** `/lms/student/dashboard.php`, `/lms/faculty/dashboard.php`, `/lms/student/my_courses.php`

## 9. [[System Admin]]
* **Purpose:** User account management, role permissions, activity audit logs, system settings, database backups, and summary reports.
* **Authorized Roles:** `admin`, `superadmin`
* **Controllers:** [`SystemController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/SystemController.php), [`ReportController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/ReportController.php), [`DashboardController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/DashboardController.php), [`LmsAdminController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php)
* **Key Routes:** `/admin/dashboard.php`, `/admin/system/users.php`, `/admin/system/audit_logs.php`, `/admin/system/backup.php`, `/admin/system/reports.php`
