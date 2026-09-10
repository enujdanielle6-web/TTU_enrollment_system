# Controllers Reference Manual

This document provides complete, verified file-level documentation for all **38 Controller classes** in the TTU Enrollment System and LMS repository.

---

## 1. Root & Public Controllers

### `HomeController.php`
- **File:** `HomeController.php`
- **Path:** `app/Controllers/HomeController.php`
- **Module:** Public Landing & Marketing
- **Feature:** University Portal Landing & Program Showcase
- **Purpose:** Renders the public-facing university homepage and program discovery catalog.
- **Responsibilities:**
  - Loads active announcements and broadcast alerts from MariaDB.
  - Queries active College degree programs and SHS academic strands for prospective student browsing.
  - Renders the public landing view (`home.php`) and marketing demo (`demo_landing.php`).
- **Key Methods:**
  - `index(Request $request, Response $response): string` — Fetches announcements and programs; renders `home.php`.
  - `demo(Request $request, Response $response): string` — Renders `demo_landing.php`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`, `App\Core\Request`, `App\Core\Response`
- **Database Interaction:** Reads `announcements`, `college_programs`, `shs_strands`.
- **Authorized Roles:** Public / Unauthenticated
- **Used By:** `GET /`, `GET /demo_landing.php`
- **Related Files:** `app/Views/home.php`, `app/Views/demo_landing.php`
- **Related Documentation:** [[Landing Page & Program Card Customization]], [[System Architecture]]

---

### `AuthController.php`
- **File:** `AuthController.php`
- **Path:** `app/Controllers/AuthController.php`
- **Module:** Authentication & Identity
- **Feature:** User Authentication, 6-Digit Email OTP Registration, Password Reset
- **Purpose:** Manages the entire public identity lifecycle, credential verification, email OTP validation, and password reset flows.
- **Responsibilities:**
  - Authenticates users by email/password with bcrypt hashing (`password_verify`).
  - Implements brute-force protection logging into `login_attempts` with IP-based throttling.
  - Enforces 6-digit email OTP verification before granting student account activation (`email_verified = 1`).
  - Dispatches OTP emails via Google SMTP PHPMailer.
  - Handles password reset requests using secure 6-digit tokens (`reset_token`, `reset_token_expires_at`).
  - Redirects authenticated users to their authorized portal based on role.
- **Key Methods:**
  - `showLogin(Request $request, Response $response): string` — Renders `auth/login.php`.
  - `login(Request $request, Response $response): void` — Validates credentials, checks brute-force attempts, initializes session.
  - `showRegister(Request $request, Response $response): string` — Renders `auth/register.php`.
  - `register(Request $request, Response $response): void` — Validates registration, inserts unverified user, dispatches 6-digit OTP email.
  - `showVerifyEmail(Request $request, Response $response): string` — Renders `auth/verify_email.php`.
  - `processVerifyEmail(Request $request, Response $response): void` — Verifies submitted OTP against `verification_code`, activates user account.
  - `resendVerification(Request $request, Response $response): void` — Generates fresh OTP with 15-minute expiry and resends email.
  - `showForgotPassword(Request $request, Response $response): string` — Renders `auth/forgot_password.php`.
  - `processForgotPassword(Request $request, Response $response): void` — Generates password reset token and emails OTP.
  - `showResetPassword(Request $request, Response $response): string` — Renders `auth/reset_password.php`.
  - `processResetPassword(Request $request, Response $response): void` — Updates password hash and clears reset token.
  - `logout(Request $request, Response $response): void` — Destroys session and redirects to `/auth/login.php`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Models\User`, `App\Core\Database`, PHPMailer
- **Database Interaction:** Reads/Writes `users`, `login_attempts`, `activity_logs`.
- **Authorized Roles:** Public / Unauthenticated
- **Used By:** `GET|POST /auth/login.php`, `/auth/register.php`, `/auth/verify_email.php`, `/auth/forgot_password.php`, `/auth/reset_password.php`, `GET /auth/logout.php`
- **Related Files:** `app/Views/auth/*.php`, `app/Views/emails/*.php`, `app/Models/User.php`
- **Related Documentation:** [[Authentication & Email Verification]], [[ADR-006 Deferred Account Creation via OTP]], [[Security Overview]]

---

### `ApplicantController.php`
- **File:** `ApplicantController.php`
- **Path:** `app/Controllers/ApplicantController.php`
- **Module:** Applicant Portal
- **Feature:** Applicant Dashboard, Profile, Tuition Assessment, Payment Proof, Slip Printing
- **Purpose:** Serves as the primary controller for applicant self-service operations post-registration.
- **Responsibilities:**
  - Renders the interactive applicant dashboard tracking application milestones.
  - Displays finalized tuition assessment details and itemized balances.
  - Handles online payment proof uploads (GCash/Bank Transfer receipts) into `uploads/payments/`.
  - Generates printable Certificate of Matriculation (COM) / enrollment slips (`printSlip`).
  - Manages applicant self-service profile and contact updates.
  - Processes applicant scholarship grant requests.
- **Key Methods:**
  - `dashboard(Request $request, Response $response): string` — Renders `applicant/dashboard.php` with milestone progress.
  - `assessment(Request $request, Response $response): string` — Renders `applicant/assessment.php` with breakdown.
  - `processPayment(Request $request, Response $response): void` — Uploads deposit proof and inserts `payment_records`.
  - `printSlip(Request $request, Response $response): string` — Renders printable official slip `applicant/print_slip.php`.
  - `profile(Request $request, Response $response): string` — Renders applicant profile editor.
  - `updateProfile(Request $request, Response $response): void` — Validates and updates user contact details.
  - `scholarships(Request $request, Response $response): string` — Lists available institutional scholarships.
  - `applyScholarship(Request $request, Response $response): void` — Submits scholarship application.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Models\Application`, `App\Models\StudentAssessment`, `App\Models\HealthRecord`, `App\Models\User`
- **Database Interaction:** Reads/Writes `applications`, `student_assessments`, `payment_records`, `scholarships`, `scholarship_applications`, `users`.
- **Authorized Roles:** `applicant`
- **Used By:** `GET /applicant/dashboard.php`, `/applicant/assessment.php`, `/applicant/print_slip.php`, `/applicant/profile.php`, `/applicant/scholarships.php`
- **Related Files:** `app/Views/applicant/dashboard.php`, `app/Views/applicant/assessment.php`, `app/Views/applicant/print_slip.php`
- **Related Documentation:** [[Applicant Portal]], [[Payment & Assessment Workflow]], [[04 - Applicant Portal Relationship Map]]

---

### `EnrollController.php`
- **File:** `EnrollController.php`
- **Path:** `app/Controllers/EnrollController.php`
- **Module:** Applicant Portal & Enrollment
- **Feature:** Multi-Step Enrollment Application Wizard & Real-time Status Tracking
- **Purpose:** Manages the multi-step online application form, irregular subject selection, and real-time status tracking.
- **Responsibilities:**
  - Renders the 5-step enrollment wizard (`applicant/enroll.php`).
  - Evaluates student type (Freshman, Regular, Transferee, Irregular).
  - Persists irregular student subject selections into `application_subject_requests` within an atomic PDO transaction.
  - Validates LRN (12 digits), birth dates, NSTP track preferences (CWTS, ROTC, LTS), and demographic data.
  - Renders `applicant/status.php` displaying admissions feedback, medical clearance status, and document rejection remarks.
- **Key Methods:**
  - `showForm(Request $request, Response $response): string` — Loads academic programs/strands and renders `applicant/enroll.php`.
  - `processForm(Request $request, Response $response): void` — Validates wizard input, creates `applications` record, persists irregular subject requests inside a transaction.
  - `status(Request $request, Response $response): string` — Queries application state, document verification remarks, and clinic status; renders `applicant/status.php`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`, `App\Models\Application`, `App\Models\User`
- **Database Interaction:** Reads/Writes `applications`, `application_subject_requests`, `users`, `college_programs`, `shs_strands`, `subjects`.
- **Authorized Roles:** `applicant`
- **Used By:** `GET|POST /applicant/enroll.php`, `POST /applicant/enroll_process.php`, `GET /applicant/status.php`
- **Related Files:** `app/Views/applicant/enroll.php`, `app/Views/applicant/status.php`
- **Related Documentation:** [[Applicant Portal]], [[Student Lifecycle Workflow]], [[04 - Applicant Portal Relationship Map]]

---

### `DocumentController.php`
- **File:** `DocumentController.php`
- **Path:** `app/Controllers/DocumentController.php`
- **Module:** Applicant Portal & Admissions
- **Feature:** Document Requirements Upload, Submission Mode Preference, Secure Viewing
- **Purpose:** Governs document uploads (PSA, Form 138, Good Moral, 2x2 Photo) and document viewing.
- **Responsibilities:**
  - Renders requirements checklist and uploaded file review cards.
  - Validates uploaded file MIME types (PDF, PNG, JPEG), file extensions, and size limits (max 5MB).
  - Stores uploaded documents with sanitized unique filenames in `uploads/documents/`.
  - Handles submission preference toggles (Online Upload vs. On-Campus Physical Submission).
  - Implements secure binary streaming (`viewDocument`) with authorization checks.
- **Key Methods:**
  - `index(Request $request, Response $response): string` — Renders `applicant/documents.php`.
  - `upload(Request $request, Response $response): void` — Validates and saves uploaded requirement file into `application_documents`.
  - `workflow(Request $request, Response $response): void` — Updates `applications.document_submission_method` (`online` vs `on_campus`).
  - `viewDocument(Request $request, Response $response): void` — Streams document binary safely with correct `Content-Type` header.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Models\ApplicationDocument`, `App\Models\Application`
- **Database Interaction:** Reads/Writes `application_documents`, `applications`.
- **Authorized Roles:** `applicant`, `admin`, `admissions`, `superadmin`
- **Used By:** `GET|POST /applicant/documents.php`, `POST /applicant/document_upload.php`, `POST /applicant/document_workflow.php`, `GET /applicant/document_view.php`
- **Related Files:** `app/Views/applicant/documents.php`, `app/Models/ApplicationDocument.php`
- **Related Documentation:** [[Document Submission Preference Workflow]], [[Security Overview]]

---

### `HealthController.php`
- **File:** `HealthController.php`
- **Path:** `app/Controllers/HealthController.php`
- **Module:** Applicant Portal & Clinic
- **Feature:** Health Profile & Medical Declaration
- **Purpose:** Collects medical history, allergies, chronic conditions, and emergency contact information from applicants.
- **Responsibilities:**
  - Renders health survey form (`applicant/health_info.php`).
  - Validates physical measurements (height, weight, blood type) and medical condition boolean flags.
  - Persists medical declarations into `health_records` with initial status `pending`.
- **Key Methods:**
  - `index(Request $request, Response $response): string` — Loads existing health record or blank template; renders `applicant/health_info.php`.
  - `process(Request $request, Response $response): void` — Validates and inserts/updates `health_records`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Models\HealthRecord`, `App\Models\Application`
- **Database Interaction:** Reads/Writes `health_records`, `applications`.
- **Authorized Roles:** `applicant`
- **Used By:** `GET /applicant/health_info.php`, `POST /applicant/health_process.php`
- **Related Files:** `app/Views/applicant/health_info.php`, `app/Models/HealthRecord.php`
- **Related Documentation:** [[Health Submission & Clearance Workflow]], [[Clinic]]

---

## 2. Administrative Controllers

### `AdmissionsController.php`
- **File:** `AdmissionsController.php`
- **Path:** `app/Controllers/Admin/Admissions/AdmissionsController.php`
- **Module:** Admissions (Admin)
- **Feature:** Application Review, Medical Clearance Gating, Section Assignment, Assessment Generation
- **Purpose:** Authoritative admissions processing controller managing intake, document validation, and student assessment creation.
- **Responsibilities:**
  - Displays Admissions review queues filtered by academic level, strand, and status.
  - Enforces mandatory clinic medical clearance gate (`health_records.status = 'verified'`) post-approval before permitting final enrollment.
  - Displays requested subjects for irregular/transferee applicants.
  - Assigns official class sections (`college_sections` / `shs_sections`).
  - Calls `AssessmentService` to generate tuition billing and freeze line items in `assessment_items`.
  - Handles bulk application status updates.
- **Key Methods:**
  - `index(Request $request, Response $response): string` — Renders `admin/admissions/dashboard.php`.
  - `review(Request $request, Response $response): string` — Renders filterable intake table `admin/admissions/review.php`.
  - `detail(Request $request, Response $response): string` — Renders applicant file `admin/admissions/detail.php`.
  - `process(Request $request, Response $response): void` — Approves/rejects application, assigns section, triggers assessment, enforces clinic gate upon enrollment.
  - `bulkProcess(Request $request, Response $response): void` — Processes multiple applications in batch.
  - `viewDocument(Request $request, Response $response): void` — Streams document binary for admissions officer inspection.
  - `uploadDocument(Request $request, Response $response): void` — Allows admissions staff to upload verified on-campus physical documents.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Services\AssessmentService`, `App\Models\Application`, `App\Models\User`
- **Database Interaction:** Reads/Writes `applications`, `application_documents`, `health_records`, `student_assessments`, `assessment_items`, `college_sections`, `shs_sections`, `activity_logs`.
- **Authorized Roles:** `admin`, `admissions`, `superadmin`
- **Used By:** Routes under `/admin/admissions/*`
- **Related Files:** `app/Views/admin/admissions/dashboard.php`, `review.php`, `detail.php`
- **Related Documentation:** [[Admissions]], [[05 - Admissions Admin Relationship Map]], [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]]

---

### `ClinicController.php`
- **File:** `ClinicController.php`
- **Path:** `app/Controllers/Admin/Clinic/ClinicController.php`
- **Module:** Clinic (Admin)
- **Feature:** Medical Profile Evaluation & Health Clearance Gating
- **Purpose:** Manages student physical measurements, pre-existing condition reviews, emergency contacts, and clearance statuses.
- **Responsibilities:**
  - Displays medical review queues for incoming applicants.
  - Allows university physicians/nurses to verify health declarations or request medical certificates.
  - Updates `health_records.status` to `verified`, `under_review`, or `correction_required`.
  - Logs medical clearance transitions in `activity_logs`.
- **Key Methods:**
  - `dashboard(Request $request, Response $response): string` — Renders `admin/clinic/clinic_dashboard.php`.
  - `index(Request $request, Response $response): string` — Renders medical clearance table `admin/clinic/medical_clearance.php`.
  - `detail(Request $request, Response $response): string` — Renders individual applicant health profile `admin/clinic/medical_detail.php`.
  - `process(Request $request, Response $response): void` — Updates clinic verification status and records doctor remarks.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Models\HealthRecord`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `health_records`, `applications`, `users`, `activity_logs`.
- **Authorized Roles:** `clinic`, `admin`, `superadmin`
- **Used By:** Routes under `/admin/clinic/*`
- **Related Files:** `app/Views/admin/clinic/clinic_dashboard.php`, `medical_clearance.php`, `medical_detail.php`
- **Related Documentation:** [[Clinic]], [[Health Submission & Clearance Workflow]], [[06 - Clinic Admin Relationship Map]]

---

### `RegistrarController.php`
- **File:** `RegistrarController.php`
- **Path:** `app/Controllers/Admin/Registrar/RegistrarController.php`
- **Module:** Registrar (Admin)
- **Feature:** Enrolled Masterlist, Server-Side Pagination, Command Center Dashboard, Final Enrollment Concurrence
- **Purpose:** Authoritative registrar operations controller managing institutional matriculation, academic catalog analytics, and student records.
- **Responsibilities:**
  - Displays executive Registrar Command Center dashboard aggregating core KPIs (enrolled, clearance queue, active sections, official IDs), balanced department shortcut hubs, and recently enrolled students live preview.
  - Renders the Enrolled Students Masterlist strictly scoped to finalized students (`a.status = 'enrolled'`) with server-side pagination (25/50/100 rows/page), dynamic search, and level/grade/program filters.
  - Computes global KPI counts (Total Enrolled, College, SHS, Official Student IDs) independently of active pagination bounds.
  - Streams CSV exports of enrolled student records via GET and POST (`exportStudents`).
  - Displays College and Senior High School Pending Finalization Queues (`payment_verified`).
  - Executes authoritative enrollment finalization (`finalizeEnrollment`), delegating to `EnrollmentService` to transition status to `enrolled`, allocate student IDs, provision emails, and assign section offerings.
- **Key Methods:**
  - `dashboard(Request $request, Response $response): string` — Aggregates academic metrics, recent enrollees, and settings; renders `admin/registrar/dashboard.php`.
  - `students(Request $request, Response $response): string` — Executes enrolled-only paginated SQL query; renders `admin/registrar/students.php`.
  - `exportStudents(Request $request, Response $response): void` — Streams enrolled students CSV masterlist download to browser.
  - `collegeQueue(Request $request, Response $response): string` — Renders College finalization queue `admin/registrar/college_queue.php`.
  - `shsQueue(Request $request, Response $response): string` — Renders SHS finalization queue `admin/registrar/shs_queue.php`.
  - `finalizeEnrollment(Request $request, Response $response): void` — Executes final matriculation transaction via `EnrollmentService`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Services\EnrollmentService`, `App\Services\StudentNumberService`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `users`, `applications`, `college_sections`, `shs_sections`, `college_programs`, `shs_strands`, `subjects`, `student_assessments`, `student_number_sequences`, `activity_logs`.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** Routes under `/admin/registrar/*`
- **Related Files:** `app/Views/admin/registrar/dashboard.php`, `students.php`, `college_queue.php`, `shs_queue.php`
- **Related Documentation:** [[Registrar]], [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]], [[07 - Registrar Admin Relationship Map]]

---

### `CollegeController.php`
- **File:** `CollegeController.php`
- **Path:** `app/Controllers/Admin/Registrar/CollegeController.php`
- **Module:** Registrar (Admin) & Curriculum
- **Feature:** College Degree Programs, Curricula Management, Interactive Curriculum Builder
- **Purpose:** Governs undergraduate degree programs, versioned curricula, and semester subject catalog bindings.
- **Responsibilities:**
  - Manages collegiate academic programs in `college_programs` (BSCS, BSIT, BSHM, etc.).
  - Manages curriculum versioning (Draft $\rightarrow$ Active $\rightarrow$ Archived) in `college_curricula`.
  - Powers the interactive Curriculum Builder matrix, binding subjects to year levels and semesters.
- **Key Methods:**
  - `programs(Request $request, Response $response): string` — Renders `admin/registrar/college_programs.php`.
  - `processProgram(Request $request, Response $response): void` — Creates/updates degree program entries.
  - `curriculum(Request $request, Response $response): string` — Renders `admin/registrar/college_curriculum.php`.
  - `processCurriculum(Request $request, Response $response): void` — Creates/versions curriculum blueprints.
  - `curriculumBuilder(Request $request, Response $response): string` — Handles GET builder UI and POST subject bindings.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `college_programs`, `college_curricula`, `college_curriculum_subjects`, `subjects`.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** Routes under `/admin/registrar/college_*`
- **Related Files:** `app/Views/admin/registrar/college_programs.php`, `college_curriculum.php`, `college_curriculum_builder.php`
- **Related Documentation:** [[Curriculum Architecture]], [[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]

---

### `ShsController.php`
- **File:** `ShsController.php`
- **Path:** `app/Controllers/Admin/Registrar/ShsController.php`
- **Module:** Registrar (Admin) & Curriculum
- **Feature:** Senior High School Strands, Curricula Management, Strand Builder
- **Purpose:** Governs Senior High School academic strands (STEM, ABM, HUMSS, TVL) and versioned Grade 11/12 curricula.
- **Responsibilities:**
  - Manages SHS strands in `shs_strands`.
  - Manages SHS curriculum versions in `shs_curricula`.
  - Powers the SHS Curriculum Builder mapping subjects across Grade 11 and Grade 12 semesters.
- **Key Methods:**
  - `strands(Request $request, Response $response): string` — Renders `admin/registrar/shs_strands.php`.
  - `processStrand(Request $request, Response $response): void` — Creates/updates strand definitions.
  - `curriculum(Request $request, Response $response): string` — Renders `admin/registrar/shs_curriculum.php`.
  - `processCurriculum(Request $request, Response $response): void` — Creates/updates SHS curriculum versions.
  - `curriculumBuilder(Request $request, Response $response): string` — Renders and processes the SHS curriculum subject matrix.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `shs_strands`, `shs_curricula`, `shs_curriculum_subjects`, `subjects`.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** Routes under `/admin/registrar/shs_*`
- **Related Files:** `app/Views/admin/registrar/shs_strands.php`, `shs_curriculum.php`, `shs_curriculum_builder.php`
- **Related Documentation:** [[Curriculum Architecture]], [[ADR-005 Curriculum Versioning and Subject Catalog Immutability]]

---

### `SubjectController.php`
- **File:** `SubjectController.php`
- **Path:** `app/Controllers/Admin/Registrar/SubjectController.php`
- **Module:** Registrar (Admin) & Curriculum
- **Feature:** Universal Subjects Catalog
- **Purpose:** Master registry for all academic courses and subject codes taught at TTU.
- **Responsibilities:**
  - Creates, edits, and soft-deletes subjects in `subjects`.
  - Enforces unique subject codes, units, subject types (Lecture vs. Laboratory), and education levels (College vs. SHS).
  - Protects catalog immutability for subjects bound to active curricula.
- **Key Methods:**
  - `index(Request $request, Response $response): string` — Renders `admin/registrar/subjects.php`.
  - `process(Request $request, Response $response): void` — Dispatches create, update, or soft-delete operations.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `subjects`, `activity_logs`.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** `GET /admin/registrar/subjects.php`, `POST /admin/registrar/subject_process.php`
- **Related Files:** `app/Views/admin/registrar/subjects.php`
- **Related Documentation:** [[Subject Catalog Immutability Architecture]], [[05 - Curriculum/Curriculum Architecture]]

---

### `SchedulerController.php`
- **File:** `SchedulerController.php`
- **Path:** `app/Controllers/Admin/Scheduler/SchedulerController.php`
- **Module:** Scheduler (Admin)
- **Feature:** Section Creation, Timetable Matrix, Room & Instructor Allocations
- **Purpose:** Manages timetable scheduling, class block sections, room allocations, and instructor assignments.
- **Responsibilities:**
  - Displays the Scheduler dashboard showing section utilization and scheduling metrics.
  - Manages College class block sections (`college_sections`) and SHS block sections (`shs_sections`).
  - Powers the interactive Timetable Matrix Builder assigning days, start/end times, rooms, and faculty to scheduled subject offerings (`college_section_subjects` / `shs_section_subjects`).
  - Enforces capacity constraints and prevents overlapping room assignments.
- **Key Methods:**
  - `dashboard(Request $request, Response $response): string` — Renders `admin/scheduler/scheduler_dashboard.php`.
  - `collegeSections(Request $request, Response $response): string` — Handles GET list and POST section creation for College.
  - `shsSections(Request $request, Response $response): string` — Handles GET list and POST section creation for SHS.
  - `builder(Request $request, Response $response): string` — Renders timetable grid `admin/scheduler/schedule_builder.php`.
  - `process(Request $request, Response $response): void` — Persists section subject schedules, days, rooms, and instructors.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `college_sections`, `college_section_subjects`, `shs_sections`, `shs_section_subjects`, `subjects`, `activity_logs`.
- **Authorized Roles:** `scheduler`, `admin`, `superadmin`
- **Used By:** Routes under `/admin/scheduler/*`
- **Related Files:** `app/Views/admin/scheduler/scheduler_dashboard.php`, `college_sections.php`, `shs_sections.php`, `schedule_builder.php`
- **Related Documentation:** [[Scheduler]], [[08 - Scheduler Admin Relationship Map]]

---

### `FinanceController.php`
- **File:** `FinanceController.php`
- **Path:** `app/Controllers/Admin/Finance/FinanceController.php`
- **Module:** Finance & Cashier (Admin)
- **Feature:** Cashier Payment Processing, Atomic Receipts, Assessment Ledgers
- **Purpose:** Governs financial collection, over-the-counter payments, bank proof verification, atomic OR issuance, and assessment auditing.
- **Responsibilities:**
  - Displays Cashier daily collections, total revenue, and pending verification queues.
  - Records cash, GCash, and bank transfer payments against student assessments.
  - Implements atomic official receipt generation (`generateAtomicReceiptNumber`) using `receipt_sequences` table with row locks.
  - Transitions application status to `payment_verified` upon minimum downpayment (₱3,000.00) or full payment.
  - Renders printable official payment receipts (`receipt.php`) loading immutable itemized line items from `assessment_items`.
- **Key Methods:**
  - `dashboard(Request $request, Response $response): string` — Renders `admin/finance/cashier_dashboard.php`.
  - `assessment(Request $request, Response $response): string` — Renders student billing ledgers `admin/finance/cashier_assessment.php`.
  - `payments(Request $request, Response $response): string` — Renders transaction queue `admin/finance/cashier_payments.php`.
  - `receipt(Request $request, Response $response): string` — Loads frozen snapshot items; renders `admin/finance/receipt.php`.
  - `process(Request $request, Response $response): void` — Records payment transaction, updates `student_assessments`, marks `payment_verified`.
  - `generateAtomicReceiptNumber(int $year): string` — Generates monotonic unique receipt formatted as `OR-YYYY-XXXXXX`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Services\AssessmentService`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `payment_records`, `student_assessments`, `assessment_items`, `receipt_sequences`, `applications`, `activity_logs`.
- **Authorized Roles:** `cashier`, `admin`, `superadmin`
- **Used By:** Routes under `/admin/finance/*`
- **Related Files:** `app/Views/admin/finance/cashier_dashboard.php`, `cashier_payments.php`, `cashier_assessment.php`, `receipt.php`
- **Related Documentation:** [[Finance]], [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]], [[ADR-009 Financial Immutability and Assessment Snapshots]], [[09 - Finance & Cashier Relationship Map]]

---

### `FeeController.php`
- **File:** `FeeController.php`
- **Path:** `app/Controllers/Admin/Finance/FeeController.php`
- **Module:** Finance & Cashier (Admin)
- **Feature:** Institutional Fee Templates Management
- **Purpose:** Configures institutional tuition schedules, miscellaneous fee packages, and laboratory rate templates.
- **Responsibilities:**
  - Lists configured fee templates categorized by Academic Level (College vs. SHS), Grade Level, Strand, and Semester.
  - Supports flat fee schedules and dynamic per-unit tuition rates (`is_per_unit = 1`).
  - Configures registration, laboratory, miscellaneous, and other institutional charges.
- **Key Methods:**
  - `index(Request $request, Response $response): string` — Renders `admin/finance/fees.php`.
  - `process(Request $request, Response $response): void` — Creates or updates fee template definitions in `fee_templates`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `fee_templates`, `activity_logs`.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** `GET /admin/finance/fees.php`, `POST /admin/finance/fee_process.php`
- **Related Files:** `app/Views/admin/finance/fees.php`
- **Related Documentation:** [[Finance]], [[Payment & Assessment Workflow]]

---

### `ScholarshipController.php`
- **File:** `ScholarshipController.php`
- **Path:** `app/Controllers/Admin/Scholarship/ScholarshipController.php`
- **Module:** Scholarship (Admin)
- **Feature:** Grants Management, Application Evaluation, Active Scholars Registry
- **Purpose:** Manages university scholarship programs, applicant grant submissions, and tuition deduction awards.
- **Responsibilities:**
  - Configures scholarship offerings (`percentage` vs `fixed` discount) in `scholarships`.
  - Reviews student scholarship applications in `scholarship_applications`.
  - Maintains the active awardees registry in `scholarship_recipients`.
  - Integrates with student assessments by factoring discounts into tuition billing.
- **Key Methods:**
  - `dashboard(Request $request, Response $response): string` — Renders `admin/scholarship/scholarship_dashboard.php`.
  - `index(Request $request, Response $response): string` — Lists grant programs in `admin/scholarship/scholarships.php`.
  - `review(Request $request, Response $response): string` — Renders incoming student applications table `scholarship_review.php`.
  - `detail(Request $request, Response $response): string` — Renders applicant grades and income documentation `scholarship_detail.php`.
  - `scholars(Request $request, Response $response): string` — Renders active scholars roster `admin/scholarship/scholars.php`.
  - `process(Request $request, Response $response): void` — Approves/rejects scholarship application and creates `scholarship_recipients` award.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `scholarships`, `scholarship_applications`, `scholarship_recipients`, `student_assessments`, `activity_logs`.
- **Authorized Roles:** `scholarship`, `admin`, `superadmin`
- **Used By:** Routes under `/admin/scholarship/*`
- **Related Files:** `app/Views/admin/scholarship/*.php`
- **Related Documentation:** [[Scholarship]], [[10 - Scholarship Admin Relationship Map]]

---

### `SystemController.php`
- **File:** `SystemController.php`
- **Path:** `app/Controllers/Admin/System/SystemController.php`
- **Module:** System Administration (Admin)
- **Feature:** User Accounts, RBAC Roles, Audit Logs, Database Backup/Restore, System Settings
- **Purpose:** Core administrative controller managing institutional user accounts, security logs, database maintenance, and global configurations.
- **Responsibilities:**
  - Manages institutional user accounts, password resets, and role assignments across all portals.
  - Enforces privilege escalation defense: only authenticated `superadmin` users can create or promote accounts to the `superadmin` role.
  - Renders the institutional audit trail feed (`audit_logs.php`) with JSON before/after state diff inspectors.
  - Displays user-specific activity history (`user_activity.php`).
  - Generates full MariaDB SQL database dumps (`backup.php`) and handles database restore uploads.
  - Manages global system settings (`system_settings`) including active school year, term dates, and cost-per-unit constants.
- **Key Methods:**
  - `dashboard(Request $request, Response $response): string` — Renders `admin/system/sysadmin_dashboard.php`.
  - `users(Request $request, Response $response): string` — Renders user management table `admin/system/users.php`.
  - `processUser(Request $request, Response $response): void` — Validates and creates/updates user identities and roles.
  - `auditLogs(Request $request, Response $response): string` — Renders immutable audit trail `admin/system/audit_logs.php`.
  - `userActivity(Request $request, Response $response): string` — Renders individual user audit trail `admin/system/user_activity.php`.
  - `backup(Request $request, Response $response): string` — Renders SQL backup and restore console `admin/system/backup.php`.
  - `processBackup(Request $request, Response $response): void` — Generates SQL dump download or executes restore.
  - `settings(Request $request, Response $response): string` — Renders configuration editor `admin/system/settings.php`.
  - `processSettings(Request $request, Response $response): void` — Updates key-value pairs in `system_settings`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Models\User`, `App\Models\ActivityLog`, `App\Core\Database`
- **Database Interaction:** Reads/Writes `users`, `activity_logs`, `system_settings`, `announcements`, and full schema.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** Routes under `/admin/system/*`
- **Related Files:** `app/Views/admin/system/*.php`, `app/Models/User.php`, `app/Models/ActivityLog.php`
- **Related Documentation:** [[System Administration]], [[11 - System Admin & Reports Relationship Map]], [[Security Overview]]

---

### `DashboardController.php`
- **File:** `DashboardController.php`
- **Path:** `app/Controllers/Admin/System/DashboardController.php`
- **Module:** Executive Administration
- **Feature:** Institutional Overview & KPI Analytics
- **Purpose:** Central executive landing screen for institutional administrators.
- **Responsibilities:**
  - Aggregates high-level metrics across all departments: total applicants, enrolled students, cashier collections, and active sections.
  - Renders Chart.js visual analytics and quick-action navigation cards.
- **Key Methods:**
  - `index(Request $request, Response $response): string` — Queries summary statistics; renders `admin/system/dashboard.php`.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads `applications`, `student_assessments`, `users`, `college_enrollments`, `shs_enrollments`.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** `GET /admin/dashboard.php`
- **Related Files:** `app/Views/admin/system/dashboard.php`
- **Related Documentation:** [[System Administration]]

---

### `ReportController.php`
- **File:** `ReportController.php`
- **Path:** `app/Controllers/Admin/System/ReportController.php`
- **Module:** Reports & Institutional Analytics
- **Feature:** Demographic Analytics & Downloadable CSV Reports
- **Purpose:** Generates institutional reports, pipeline throughput metrics, and downloadable CSV data exports.
- **Responsibilities:**
  - Displays reporting dashboards covering enrollment demographics, financial revenue, scholarship impact, and clinic clearances.
  - Streams formatted CSV report files to the browser with appropriate attachment headers.
- **Key Methods:**
  - `index(Request $request, Response $response): string` — Renders `admin/system/reports.php`.
  - `export(Request $request, Response $response): void` — Generates and streams CSV file download.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads `applications`, `payment_records`, `scholarships`, `scholarship_recipients`, `health_records`, `users`.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** `GET /admin/system/reports.php`, `POST /admin/system/reports_export.php`
- **Related Files:** `app/Views/admin/system/reports.php`
- **Related Documentation:** [[Reports Overview]], [[13 - Reports/Reports Overview]]

---

### `LmsAdminController.php`
- **File:** `LmsAdminController.php`
- **Path:** `app/Controllers/Admin/LmsAdminController.php`
- **Module:** LMS Administration
- **Feature:** Automated Course Shell Provisioning
- **Purpose:** Bridges academic section schedules to LMS course shells.
- **Responsibilities:**
  - Inspects timetabled section subjects (`college_section_subjects` / `shs_section_subjects`).
  - Generates corresponding `lms_courses` instances mapped to instructors and enrolled student rosters.
- **Key Methods:**
  - `courseGenerator(Request $request, Response $response): string` — Renders `admin/system/lms_course_generator.php`.
  - `generateLmsCourse(Request $request, Response $response): void` — Generates active LMS course shells from class sections.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads `college_section_subjects`, `shs_section_subjects`, `subjects`; Writes `lms_courses`.
- **Authorized Roles:** `admin`, `superadmin`
- **Used By:** `GET /admin/lms/generator`, `POST /admin/lms/generate`
- **Related Files:** `app/Views/admin/system/lms_course_generator.php`
- **Related Documentation:** [[LMS]], [[LMS_Database_Architecture]]

---

## 3. Internal API & AJAX Controllers

### `AdminApiController.php`
- **File:** `AdminApiController.php`
- **Path:** `app/Controllers/Api/AdminApiController.php`
- **Module:** Internal API (Admin)
- **Feature:** Administrative Asynchronous Data Endpoints
- **Purpose:** Powers dynamic administrative dropdowns, curriculum subject previews, and enrollment summaries.
- **Responsibilities:**
  - Returns JSON payloads for client-side dropdown cascade (e.g. program selection $\rightarrow$ active curricula).
  - Supplies subject catalog previews when configuring section offerings.
- **Key Methods:**
  - `getCurriculaByProgram(Request $request, Response $response): void` — Returns JSON list of curricula for a given `program_id`.
  - `getCurriculumSubjectsPreview(Request $request, Response $response): void` — Returns JSON list of subjects in a curriculum version.
  - `getEnrollmentSummary(Request $request, Response $response): void` — Returns JSON throughput statistics.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads `college_curricula`, `college_curriculum_subjects`, `subjects`, `applications`.
- **Authorized Roles:** `admin`, `scheduler`, `admissions`, `superadmin`
- **Used By:** `GET /admin/ajax/get_curricula_by_program.php`, `/admin/ajax/get_curriculum_subjects_preview.php`, `/admin/ajax/get_enrollment_summary.php`
- **Related Documentation:** [[API Documentation]], [[08 - API & AJAX/API Documentation]]

---

### `ApplicantApiController.php`
- **File:** `ApplicantApiController.php`
- **Path:** `app/Controllers/Api/ApplicantApiController.php`
- **Module:** Internal API (Applicant)
- **Feature:** Dynamic Enrollment Form & Schedule Fetchers
- **Purpose:** Supplies live curriculum previews and section timetable offerings to applicant wizard forms.
- **Responsibilities:**
  - Returns live subject offerings for selected grade/year levels.
  - Dynamically transforms generic `NSTP101` placeholders into the applicant's selected NSTP track (`CWTS101`, `ROTC101`, `LTS101`).
  - Returns available class sections and timetables for schedule selection.
- **Key Methods:**
  - `getCurriculum(Request $request, Response $response): void` — Returns JSON subject list for selected term.
  - `getFullCurriculum(Request $request, Response $response): void` — Returns complete multi-year curriculum map.
  - `getSchedule(Request $request, Response $response): void` — Returns timetable matrix for selected section.
  - `getSections(Request $request, Response $response): void` — Returns available section blocks.
  - `getSectionSubjects(Request $request, Response $response): void` — Returns scheduled subjects within a section.
  - `getSubjectSchedules(Request $request, Response $response): void` — Returns individual subject schedule offerings for irregular students.
- **Dependencies & Imports:** `App\Core\BaseController`, `App\Core\Database`
- **Database Interaction:** Reads `college_curricula`, `college_curriculum_subjects`, `shs_curricula`, `shs_curriculum_subjects`, `college_sections`, `college_section_subjects`, `subjects`.
- **Authorized Roles:** `applicant`
- **Used By:** `GET /applicant/api_get_curriculum.php`, `/applicant/api_get_sections.php`, `/applicant/api_get_schedule.php`, etc.
- **Related Documentation:** [[API Documentation]], [[National Service Training Program (NSTP) Architecture]]

---

## 4. Learning Management System (LMS) Controllers

### `LmsAuthController.php`
- **Path:** `app/Controllers/Lms/LmsAuthController.php`
- **Purpose:** Dedicated authentication handler for LMS Student and Faculty portal logins.
- **Key Methods:** `showFacultyLogin`, `showStudentLogin`, `loginProcess`, `logoutStudent`, `logoutFaculty`.
- **Authorized Roles:** Public / Student / Faculty

### `StudentController.php`
- **Path:** `app/Controllers/Lms/StudentController.php`
- **Purpose:** Primary LMS student controller rendering dynamic course dashboards, study streaks, active courses, profile, and messaging.
- **Key Methods:** `dashboard`, `course`, `myCourses`, `profile`, `messages`.
- **Authorized Roles:** `applicant` (enrolled students)

### `FacultyController.php`
- **Path:** `app/Controllers/Lms/FacultyController.php`
- **Purpose:** Primary LMS faculty controller managing class rosters, course syllabi, module authoring, and learning material uploads.
- **Key Methods:** `dashboard`, `course`, `createModule`, `uploadMaterial`, `profile`, `messages`.
- **Authorized Roles:** `faculty`, `admin`

### `StudentAssignmentController.php` & `FacultyAssignmentController.php`
- **Student Path:** `app/Controllers/Lms/StudentAssignmentController.php` (`index`, `show`, `submit`)
- **Faculty Path:** `app/Controllers/Lms/FacultyAssignmentController.php` (`index`, `create`, `store`, `edit`, `update`, `submissions`, `grade`)
- **Purpose:** Complete assignment workflow from faculty prompt authoring to student file submissions and faculty score grading.

### `StudentQuizController.php` & `FacultyQuizController.php`
- **Student Path:** `app/Controllers/Lms/StudentQuizController.php` (`index`, `show`, `start`, `attempt`, `submit`, `result`)
- **Faculty Path:** `app/Controllers/Lms/FacultyQuizController.php` (`index`, `create`, `store`, `edit`, `update`, `questions`, `storeQuestion`, `results`)
- **Purpose:** Timed online quiz engine supporting multiple choice, true/false, and short essay authoring, student timed attempts, and automated score calculations.

### `StudentGradebookController.php` & `FacultyGradebookController.php`
- **Paths:** `app/Controllers/Lms/StudentGradebookController.php` / `FacultyGradebookController.php` (`index`)
- **Purpose:** Evaluates assignment and quiz grades, computing term grade weighted averages and performance matrices.

### `StudentAttendanceController.php` & `FacultyAttendanceController.php`
- **Paths:** `app/Controllers/Lms/StudentAttendanceController.php` / `FacultyAttendanceController.php` (`index`, `create`, `store`, `edit`, `update`)
- **Purpose:** Logs daily course session attendance (`present`, `late`, `absent`, `excused`) and calculates attendance compliance rates.

### `StudentAnnouncementController.php` & `FacultyAnnouncementController.php`
- **Paths:** `app/Controllers/Lms/StudentAnnouncementController.php` / `FacultyAnnouncementController.php` (`index`, `create`, `store`, `edit`, `update`)
- **Purpose:** Manages course-specific broadcast notices between professors and enrolled students.

### `StudentCalendarController.php` & `FacultyCalendarController.php`
- **Paths:** `app/Controllers/Lms/StudentCalendarController.php` / `FacultyCalendarController.php` (`index`)
- **Purpose:** Aggregates assignment deadlines, quiz windows, and lecture sessions onto an interactive monthly calendar.

### `DownloadController.php`
- **Path:** `app/Controllers/Lms/DownloadController.php`
- **Purpose:** Secure binary delivery endpoint streaming course lecture materials and student assignment submissions with role authorization checks.
- **Key Methods:** `downloadMaterial`, `downloadSubmission`.

---
**Related:**
- [[00 - File Reference Index]]
- [[02 - Models Reference]]
- [[03 - Services Reference]]
- [[04 - Core & Middleware Reference]]
- [[05 - Views Catalog & Template Mapping]]
