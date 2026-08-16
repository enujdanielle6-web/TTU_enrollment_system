# Module Index

The TTU system is divided into several logical modules mapped to specific user roles.

## 1. [[Applicant Portal]]
**Purpose:** Allows students to register, apply, upload documents, and view their assessment/schedule.
**Users:** `applicant`
**Controllers:** `ApplicantController`, `EnrollController`, `DocumentController`, `HealthController`

## 2. [[Admissions Module]]
**Purpose:** Administrative evaluation of incoming applications.
**Users:** `admissions`, `admin`
**Controllers:** `AdmissionsController`

## 3. [[Registrar Module]]
**Purpose:** Management of the [[Curriculum Architecture]], subjects, and student academic records.
**Users:** `registrar` (often maps to `admin` role)
**Controllers:** `RegistrarController`, `SubjectController`, `CollegeController`, `ShsController`

## 4. [[Scheduler Module]]
**Purpose:** Creates and manages course sections and faculty assignments.
**Users:** `scheduler`
**Controllers:** `SchedulerController`

## 5. [[Finance and Cashier Module]]
**Purpose:** Evaluates fee templates, applies scholarships, generates assessments, and processes payments.
**Users:** `cashier`, `admin`
**Controllers:** `FinanceController`, `FeeController`

## 6. [[Scholarship Module]]
**Purpose:** Manages available scholarships and reviews applications.
**Users:** `scholarship`
**Controllers:** `ScholarshipController`

## 7. [[Clinic Module]]
**Purpose:** Validates student health records submitted during the application process.
**Users:** `clinic`
**Controllers:** `ClinicController`

## 8. [[LMS Module]]
**Purpose:** Basic learning management (modules, assignments, submissions).
**Users:** `faculty`, `applicant`
**Controllers:** `Lms\StudentController`, `Lms\FacultyController`
**Status:** `Partially Implemented` / `Unknown depth of usage`
