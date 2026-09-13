# 05. ENROLLMENT SYSTEM

## Subsystem Architecture
The Enrollment System coordinates the journey from prospective applicant to enrolled student.

### 1. Applicant Portal
- Self-registration, OTP email confirmation, demographic data entry.
- Document upload (`DocumentController`): PDF, PNG, JPG validation via `finfo` MIME sniffing. Max 5MB.
- Health record intake (`HealthController`): Medical survey and emergency contact info.

### 2. Admissions Committee Workflow
- Application queue review (`AdmissionsController::review`).
- Document inspection and approval/rejection feedback.
- Transition from `pending` -> `under_review` -> `approved`.

### 3. Medical Clearance
- Managed by `ClinicController` at `/sia/admin/clinic/medical_clearance.php`.
- Evaluates health records: `verified`, `correction_required`, `rejected`.

### 4. Curriculum & Program Structure
- `SubjectController`: Catalog of lecture/laboratory courses with unit weights.
- `CollegeController` & `ShsController`: Curricula templates mapping subjects across academic years and terms.

### 5. Timetable & Sections
- `SchedulerController`: Builds section offerings and assigns day/time/room schedules.
- Checks room conflicts across timetable slots.

### 6. Billing & Cashiering
- `AssessmentService`: Automatically generates fee ledger based on units (College) or fee templates (SHS).
- `FinanceController`: Records tuition payments with row-level locking (`FOR UPDATE`). Updates payment status (`partial`/`paid`).

### 7. Enrollment Finalization
- `EnrollmentService::finalizeEnrollment()`:
  1. Assigns Student ID (`StudentNumberService`).
  2. Generates institutional email (`@ttu.edu.ph`) and temporary password.
  3. Promotes user role to `student`.
  4. Enrolls student in section subjects (`college_enrollments` / `shs_enrollments`).
  5. Updates application status to `enrolled`.
  6. Dispatches credentials via PHPMailer.
