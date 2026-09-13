# Domain Services Reference Manual

This document provides complete, verified file-level documentation for all **9 Domain Service classes** located in `app/Services/`.

In accordance with TTU's **Hybrid MVC architecture**, Domain Services encapsulate complex, multi-entity, or concurrency-sensitive business logic that would otherwise cause controller bloat or logic duplication, while remaining completely stateless and non-abstracting.

---

## 1. Enrollment & Financial Domain Services

### `StudentNumberService.php`
- **File:** `StudentNumberService.php`
- **Path:** `app/Services/StudentNumberService.php`
- **Module:** Registrar & Identity Architecture
- **Feature:** Atomic, Race-Free Sequential Student ID Generation
- **Purpose:** Generates unique institutional student numbers formatted as `YYYY-XXXXXX` (e.g. `2026-000001`) with absolute concurrency protection.
- **Responsibilities:**
  - Employs the dedicated `student_number_sequences` table in MariaDB (`id` PK AUTO_INC, `sequence_year` UNIQUE, `current_value` INT, `updated_at` TIMESTAMP).
  - Executes atomic sequence increment via `INSERT INTO student_number_sequences (sequence_year, current_value) VALUES (:year, 1) ON DUPLICATE KEY UPDATE current_value = current_value + 1` within the caller's active PDO transaction.
  - Formats numbers with zero-padded 6-digit sequences, preventing sequence collisions under high concurrent admissions/finalization traffic.
  - Automatically seeds the initial sequence value from the highest existing student number in `users` if an unseeded year is encountered.
- **Key Methods:**
  - `static generate(int $year, PDO $pdo): string` — Primary generation method executing atomic lock, incrementing counter, and returning `YYYY-XXXXXX`.
  - `generateNumber(int $year, PDO $pdo): string` — Instance wrapper method delegating directly to `generate()`.
  - `static getCurrentSequence(int $year, PDO $pdo): int` — Non-mutating inspection method reading the current sequence value.
- **Dependencies & Imports:** PDO, `PDOException`
- **Database Interaction:** Reads/Updates `student_number_sequences` and queries fallback max `users.student_number`.
- **Used By:** `App\Services\EnrollmentService`, `App\Controllers\Admin\Registrar\RegistrarController`.
- **Related Documentation:** [[ADR-010 Domain Service Layer Extraction and Atomic Sequences]], [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]]

---

### `AssessmentService.php`
- **File:** `AssessmentService.php`
- **Path:** `app/Services/AssessmentService.php`
- **Module:** Finance & Admissions
- **Feature:** Authoritative Tuition & Institutional Fee Math, Line-Item Snapshotting
- **Purpose:** Encapsulates the university's statutory tuition formulas and permanently freezes itemized billing line items in `assessment_items`.
- **Responsibilities:**
  - Determines tuition assessment rules based on Academic Level (College vs. SHS), Grade/Year Level, Strand, and Semester.
  - Evaluates lecture course units, laboratory course units, and per-unit billing rates (`fee_templates.is_per_unit = 1`).
  - Applies approved scholarship percentage or fixed-amount deductions from `scholarship_recipients`.
  - Calculates downpayment thresholds (statutory minimum of ₱3,000.00 or full net assessment if less).
  - Automatically invokes `snapshotAssessmentItems()` helper to freeze itemized breakdowns into `assessment_items`, preventing retroactive rate changes if fee schedules are altered mid-year.
- **Key Methods:**
  - `static generateAssessment(int $applicationId, ?int $feeTemplateId, PDO $pdo): int` — Calculates tuition charges, inserts `student_assessments`, snapshots line items into `assessment_items`, and returns new assessment ID.
  - `static recalculateAssessment(int $assessmentId, PDO $pdo): bool` — Recalculates assessment following scholarship grant awards or unit adjustments.
  - `static getAssessmentBreakdown(int $assessmentId, PDO $pdo): array` — Retrieves frozen line items from `assessment_items` for official receipt and COM rendering.
- **Dependencies & Imports:** PDO, `App\Core\Database`, `snapshotAssessmentItems()` in `app/Helpers/functions.php`.
- **Database Interaction:** Reads `applications`, `fee_templates`, `college_sections`, `shs_sections`, `subjects`, `scholarship_recipients`; Writes `student_assessments`, `assessment_items`.
- **Used By:** `AdmissionsController`, `FinanceController`, `ApplicantController`.
- **Related Documentation:** [[ADR-009 Financial Immutability and Assessment Snapshots]], [[Payment & Assessment Workflow]], [[Finance]]

---

### `EnrollmentService.php`
- **File:** `EnrollmentService.php`
- **Path:** `app/Services/EnrollmentService.php`
- **Module:** Registrar & Student Lifecycle
- **Feature:** Authoritative Matriculation Orchestration & Credential Provisioning
- **Purpose:** Orchestrates the authoritative state transition from `payment_verified` to `enrolled`, allocating student numbers, provisioning institutional emails, assigning section subjects, and queuing welcome credentials.
- **Responsibilities:**
  - Verifies application qualification: ensures status is `payment_verified` and section is assigned.
  - Locks the application record within an atomic PDO transaction (`FOR UPDATE`).
  - Calls `StudentNumberService::generate()` to allocate a guaranteed-unique institutional student ID.
  - Provisions institutional `@ttu.edu.ph` email addresses formatted as `first.last@ttu.edu.ph` (handling name collision deduplication with numeric suffixes).
  - Enforces password resets on initial login (`force_password_reset = 1`).
  - Enrolls student into official course section offerings (`college_enrollments` or `shs_enrollments`).
  - Transitions `applications.status = 'enrolled'`.
  - Dispatches branded HTML welcome email with temporary credentials via PHPMailer Google SMTP.
  - Logs immutable audit trail in `activity_logs`.
- **Key Methods:**
  - `static finalizeEnrollment(int $applicationId, int $registrarId, PDO $pdo): array` — Executes complete matriculation transaction; returns student number and credentials summary.
  - `static assignSectionSubjects(int $applicationId, int $sectionId, string $level, PDO $pdo): int` — Maps section timetable offerings into official student enrollment bridge tables.
- **Dependencies & Imports:** `App\Services\StudentNumberService`, `App\Core\Database`, PHPMailer, PDO.
- **Database Interaction:** Reads/Writes `applications`, `users`, `college_sections`, `college_section_subjects`, `college_enrollments`, `shs_sections`, `shs_section_subjects`, `shs_enrollments`, `activity_logs`.
- **Used By:** `RegistrarController@finalizeEnrollment`.
- **Related Documentation:** [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]], [[ADR-010 Domain Service Layer Extraction and Atomic Sequences]], [[Student Lifecycle Workflow]]

---

## 2. Learning Management System (LMS) Domain Services

### `LmsService.php`
- **Path:** `app/Services/LmsService.php`
- **Module:** LMS Core
- **Purpose:** Core LMS domain aggregator managing cross-course rosters, module syllabi, learning materials, student submission workflows, study streaks, and deadline alerts.
- **Key Methods:**
  - `getStudentCourses(int $userId): array` — Fetches all courses student is enrolled in.
  - `isStudentAuthorizedForCourse(int $userId, int $courseId): bool` — Enforces enrollment authorization checks.
  - `getFacultyCourses(int $facultyId): array` — Fetches courses taught by instructor.
  - `getModulesWithMaterialsForCourse(int $courseId): array` — Hierarchical course syllabus aggregator.
  - `createModule(int $courseId, string $title, string $desc): int` — Creates syllabus chapter.
  - `createMaterial(int $moduleId, string $title, string $desc, string $path, string $type): int` — Attaches lecture asset.
  - `getAssignmentsByCourse(int $courseId): array` — Lists assignments.
  - `submitAssignment(int $assignmentId, int $userId, ?string $text, ?string $file): int` — Stores student submission.
  - `gradeSubmission(int $submissionId, float $grade, string $feedback): bool` — Grades submission.
  - `getStudentUpcomingDeadlines(int $userId, int $days = 7): array` — Cross-course deadline queue.
  - `getStudentStreak(int $userId): int` — Computes consecutive daily study activity.

---

### `LmsQuizService.php`
- **Path:** `app/Services/LmsQuizService.php`
- **Module:** LMS Assessments
- **Purpose:** Online timed examination engine managing quiz authoring, question banks, student timed attempts, and automated grading.
- **Key Methods:**
  - `getQuizzesByCourse(int $courseId): array` — Lists quizzes.
  - `createQuiz(int $courseId, array $data): int` — Authors quiz with time limit and passing score.
  - `addQuestion(int $quizId, string $text, string $type, float $pts): int` — Adds question (`multiple_choice`, `true_false`, `essay`).
  - `addChoice(int $questionId, string $text, bool $isCorrect): int` — Adds choice option.
  - `startAttempt(int $quizId, int $userId): int` — Initializes timed attempt in `lms_quiz_attempts`.
  - `submitAttempt(int $attemptId, array $answers): array` — Evaluates student responses, calculates score, determines pass/fail status.

---

### `LmsGradebookService.php`
- **Path:** `app/Services/LmsGradebookService.php`
- **Module:** LMS Gradebook
- **Purpose:** Aggregates assignment submission scores and quiz attempt results into weighted term gradebooks.
- **Key Methods:**
  - `getCourseGradebook(int $courseId): array` — Returns full class roster grade matrix for faculty.
  - `getStudentGradebook(int $courseId, int $userId): array` — Returns individual student grade breakdown.

---

### `LmsAttendanceService.php`
- **Path:** `app/Services/LmsAttendanceService.php`
- **Module:** LMS Attendance
- **Purpose:** Manages lecture attendance sessions and compliance percentages.
- **Key Methods:**
  - `createSession(int $courseId, string $date, string $title): int` — Opens attendance session.
  - `saveAttendance(int $sessionId, array $records): bool` — Records `present`, `late`, `absent`, `excused` states.
  - `getStudentAttendanceHistory(int $courseId, int $userId): array` — Computes student attendance rate.

---

### `LmsAnnouncementService.php`
- **Path:** `app/Services/LmsAnnouncementService.php`
- **Module:** LMS Communications
- **Purpose:** Manages course-level broadcast announcements authored by course faculty.
- **Key Methods:**
  - `getCourseAnnouncements(int $courseId): array` — Returns course announcements.
  - `createAnnouncement(int $courseId, int $authorId, string $title, string $content): int` — Publishes notice.

---

### `LmsCalendarService.php`
- **Path:** `app/Services/LmsCalendarService.php`
- **Module:** LMS Calendar
- **Purpose:** Synthesizes course deadlines, scheduled quizzes, and lecture sessions into normalized FullCalendar event streams.
- **Key Methods:**
  - `getStudentCalendarEvents(int $userId): array` — Returns JSON event array for student monthly calendar.
  - `getFacultyCalendarEvents(int $facultyId): array` — Returns event array for faculty teaching calendar.

---

## 3. Domain Repositories (`app/Repositories/`)

### `EnrollmentRepositoryInterface.php`
- **Path:** `app/Repositories/EnrollmentRepositoryInterface.php`
- **Module:** Domain Data Access Contract
- **Purpose:** Defines the standardized interface for student course and subject retrieval across College and SHS academic tiers.
- **Key Methods:**
  - `getActiveStudentCourses(int $userId): array` — Fetches enrolled subjects with timetables and section metadata for the active term.

---

### `CollegeEnrollmentRepository.php`
- **Path:** `app/Repositories/CollegeEnrollmentRepository.php`
- **Implements:** `EnrollmentRepositoryInterface`
- **Module:** College Academic Records & Portal Delivery
- **Purpose:** Executes optimized SQL queries joining `college_enrollments`, `applications`, `subjects`, `college_sections`, and `college_section_subjects`.
- **Key Methods:**
  - `getActiveStudentCourses(int $userId): array` — Retrieves all enrolled subjects for a college student (`applications.status IN ('enrolled', 'approved')`), with graceful fallback to section-level subjects if individual enrollment rows are not yet populated.
- **Database Tables:** `college_enrollments`, `applications`, `subjects`, `college_sections`, `college_section_subjects`.

---

### `ShsEnrollmentRepository.php`
- **Path:** `app/Repositories/ShsEnrollmentRepository.php`
- **Implements:** `EnrollmentRepositoryInterface`
- **Module:** Senior High School Academic Records & Portal Delivery
- **Purpose:** Executes queries joining `shs_enrollments`, `applications`, `subjects`, `shs_sections`, and `shs_section_subjects`.
- **Key Methods:**
  - `getActiveStudentCourses(int $userId): array` — Retrieves all enrolled subjects for an SHS student with fallback to `shs_section_subjects`.
- **Database Tables:** `shs_enrollments`, `applications`, `subjects`, `shs_sections`, `shs_section_subjects`.

---
**Related:**
- [[00 - File Reference Index]]
- [[01 - Controllers Reference]]
- [[02 - Models Reference]]
- [[04 - Core & Middleware Reference]]
- [[ADR-010 Domain Service Layer Extraction and Atomic Sequences]]

