# Views Catalog & Template Mapping Manual

This document provides a complete, exhaustive inventory of all **104 PHP view templates** in `app/Views/`, mapping each template to its parent rendering controller, action, layout wrapper components, and functional purpose.

---

## 1. Public & Marketing Templates

| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/home.php` | `HomeController@index` | `components/header.php`, `navbar.php`, `footer.php` | University public landing page, active announcements carousel, degree programs showcase. |
| `app/Views/demo_landing.php` | `HomeController@demo` | `components/header.php`, `navbar.php`, `footer.php` | Marketing demonstration landing page. |

---

## 2. Authentication & Identity Templates

| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/auth/login.php` | `AuthController@showLogin` | `components/header.php`, `footer.php` | Multi-portal login form (Superadmin, Admin, Admissions, Cashier, Clinic, Registrar, Student). |
| `app/Views/auth/register.php` | `AuthController@showRegister` | `components/header.php`, `footer.php` | Public applicant registration form with password strength validation. |
| `app/Views/auth/verify_email.php` | `AuthController@showVerifyEmail` | `components/header.php`, `footer.php` | 6-digit email OTP verification screen with auto-focus and 60-second cooldown timer. |
| `app/Views/auth/forgot_password.php` | `AuthController@showForgotPassword` | `components/header.php`, `footer.php` | Password reset request form collecting registered email. |
| `app/Views/auth/reset_password.php` | `AuthController@showResetPassword` | `components/header.php`, `footer.php` | Password reset submission form verifying 6-digit token and updating bcrypt password. |
| `app/Views/auth/lms_student_login.php` | `LmsAuthController@showStudentLogin` | `components/header.php`, `footer.php` | Dedicated student LMS login screen with student number auto-formatting. |
| `app/Views/auth/lms_faculty_login.php` | `LmsAuthController@showFacultyLogin` | `components/header.php`, `footer.php` | Dedicated faculty LMS login screen. |

---

## 3. Applicant Self-Service Portal Templates

| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/applicant/dashboard.php` | `ApplicantController@dashboard` | `components/applicant_navbar.php`, `header.php`, `footer.php` | Applicant status dashboard with interactive milestone progress stepper. |
| `app/Views/applicant/enroll.php` | `EnrollController@showForm` | `components/applicant_navbar.php`, `header.php`, `footer.php` | 5-step interactive enrollment application wizard (Freshman, Regular, Irregular). |
| `app/Views/applicant/documents.php` | `DocumentController@index` | `components/applicant_navbar.php`, `header.php`, `footer.php` | Upload portal for Form 138, PSA, Good Moral, 2x2 Photo, and on-campus toggle. |
| `app/Views/applicant/health_info.php` | `HealthController@index` | `components/applicant_navbar.php`, `header.php`, `footer.php` | Medical history questionnaire and health declaration form. |
| `app/Views/applicant/status.php` | `EnrollController@status` | `components/applicant_navbar.php`, `header.php`, `footer.php` | Real-time status tracker, admissions notes, and itemized document correction feedback card. |
| `app/Views/applicant/assessment.php` | `ApplicantController@assessment` | `components/applicant_navbar.php`, `header.php`, `footer.php` | Tuition billing ledger preview and online bank deposit proof upload interface. |
| `app/Views/applicant/print_slip.php` | `ApplicantController@printSlip` | Dedicated `@media print` | Printable official Certificate of Matriculation (COM) / enrollment slip. |
| `app/Views/applicant/profile.php` | `ApplicantController@profile` | `components/applicant_navbar.php`, `header.php`, `footer.php` | Applicant personal profile and contact details editor. |
| `app/Views/applicant/scholarships.php` | `ApplicantController@scholarships` | `components/applicant_navbar.php`, `header.php`, `footer.php` | Institutional scholarship grant browsing and self-service application form. |

---

## 4. Administrative Subsystem Templates

### Admissions Admin
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/admin/admissions/dashboard.php` | `AdmissionsController@index` | `components/admin_navbar.php`, `sidebar.php` | Admissions intake analytics, throughput pipeline, and status summaries. |
| `app/Views/admin/admissions/review.php` | `AdmissionsController@review` | `components/admin_navbar.php`, `sidebar.php` | Filterable applications review queue with search, strand, and status filters. |
| `app/Views/admin/admissions/detail.php` | `AdmissionsController@detail` | `components/admin_navbar.php`, `sidebar.php` | Comprehensive applicant review screen: documents, medical gate, and section assignment. |

### Clinic Admin
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/admin/clinic/clinic_dashboard.php` | `ClinicController@dashboard` | `components/admin_navbar.php`, `sidebar.php` | Clinic clearance analytics, verified counts, and medical condition statistics. |
| `app/Views/admin/clinic/medical_clearance.php` | `ClinicController@index` | `components/admin_navbar.php`, `sidebar.php` | Filterable medical evaluation queue for incoming applicants. |
| `app/Views/admin/clinic/medical_detail.php` | `ClinicController@detail` | `components/admin_navbar.php`, `sidebar.php` | Detailed medical profile, physical measurements, chronic conditions, and physician approval. |

### Finance & Cashier Admin
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/admin/finance/cashier_dashboard.php` | `FinanceController@dashboard` | `components/admin_navbar.php`, `sidebar.php` | Cashier daily collections, total revenue trends, and pending verification queues. |
| `app/Views/admin/finance/cashier_payments.php` | `FinanceController@payments` | `components/admin_navbar.php`, `sidebar.php` | Payment recording modal, bank proof review, and downpayment approval. |
| `app/Views/admin/finance/cashier_assessment.php` | `FinanceController@assessment` | `components/admin_navbar.php`, `sidebar.php` | Searchable student assessment ledgers and payment status auditor. |
| `app/Views/admin/finance/receipt.php` | `FinanceController@receipt` | Dedicated `@media print` | Printable official university receipt (`OR-YYYY-XXXXXX`) with frozen line items. |
| `app/Views/admin/finance/fees.php` | `FeeController@index` | `components/admin_navbar.php`, `sidebar.php` | Fee template configuration console for tuition rates, lab fees, and misc fees. |

### Registrar Admin
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/admin/registrar/dashboard.php` | `RegistrarController@dashboard` | `components/admin_navbar.php` | Executive Registrar Command Center with real-time KPIs, balanced department hubs, and live enrolled roster. |
| `app/Views/admin/registrar/students.php` | `RegistrarController@students` | `components/admin_navbar.php` | Official Student Masterlist strictly for enrolled students (`status = 'enrolled'`), with server-side pagination and CSV export. |
| `app/Views/admin/registrar/college_queue.php` | `RegistrarController@collegeQueue` | `components/admin_navbar.php`, `sidebar.php` | College pending finalization queue (`payment_verified` state). |
| `app/Views/admin/registrar/shs_queue.php` | `RegistrarController@shsQueue` | `components/admin_navbar.php`, `sidebar.php` | Senior High School pending finalization queue. |
| `app/Views/admin/registrar/subjects.php` | `SubjectController@index` | `components/admin_navbar.php`, `sidebar.php` | Universal subjects catalog with create, edit, and soft-delete modals. |
| `app/Views/admin/registrar/college_programs.php` | `CollegeController@programs` | `components/admin_navbar.php`, `sidebar.php` | College degree program definitions (BSCS, BSIT, etc.). |
| `app/Views/admin/registrar/college_curriculum.php` | `CollegeController@curriculum` | `components/admin_navbar.php`, `sidebar.php` | Versioned College curriculum list (Draft, Active, Archived). |
| `app/Views/admin/registrar/college_curriculum_builder.php` | `CollegeController@curriculumBuilder` | `components/admin_navbar.php`, `sidebar.php` | Interactive College curriculum matrix builder. |
| `app/Views/admin/registrar/shs_strands.php` | `ShsController@strands` | `components/admin_navbar.php`, `sidebar.php` | SHS academic strands manager (STEM, ABM, HUMSS, TVL). |
| `app/Views/admin/registrar/shs_curriculum.php` | `ShsController@curriculum` | `components/admin_navbar.php`, `sidebar.php` | Versioned SHS curriculum blueprints. |
| `app/Views/admin/registrar/shs_curriculum_builder.php` | `ShsController@curriculumBuilder` | `components/admin_navbar.php`, `sidebar.php` | Interactive SHS curriculum builder mapping Grade 11 & 12 subjects. |

### Scheduler Admin
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/admin/scheduler/scheduler_dashboard.php` | `SchedulerController@dashboard` | `components/admin_navbar.php`, `sidebar.php` | Section utilization rates, room occupancy, and schedule counters. |
| `app/Views/admin/scheduler/college_sections.php` | `SchedulerController@collegeSections` | `components/admin_navbar.php`, `sidebar.php` | College block section creator with program and curriculum links. |
| `app/Views/admin/scheduler/shs_sections.php` | `SchedulerController@shsSections` | `components/admin_navbar.php`, `sidebar.php` | SHS block section creator with strand and adviser assignments. |
| `app/Views/admin/scheduler/schedule_builder.php` | `SchedulerController@builder` | `components/admin_navbar.php`, `sidebar.php` | Visual timetable matrix grid assigning days, times, rooms, and instructors. |

### Scholarship Admin
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/admin/scholarship/scholarship_dashboard.php` | `ScholarshipController@dashboard` | `components/admin_navbar.php`, `sidebar.php` | Financial aid budget utilization meters and award statistics. |
| `app/Views/admin/scholarship/scholarships.php` | `ScholarshipController@index` | `components/admin_navbar.php`, `sidebar.php` | Grant offerings editor (percentage vs. fixed discount amounts). |
| `app/Views/admin/scholarship/scholarship_review.php` | `ScholarshipController@review` | `components/admin_navbar.php`, `sidebar.php` | Filterable table of incoming student scholarship applications. |
| `app/Views/admin/scholarship/scholarship_detail.php` | `ScholarshipController@detail` | `components/admin_navbar.php`, `sidebar.php` | Applicant review screen with academic grades and income documentation. |
| `app/Views/admin/scholarship/scholars.php` | `ScholarshipController@scholars` | `components/admin_navbar.php`, `sidebar.php` | Masterlist of approved scholars and active discount awards. |

### System Administration
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/admin/system/sysadmin_dashboard.php` | `SystemController@dashboard` | `components/admin_navbar.php`, `sidebar.php` | Executive system administrative console and server health metrics. |
| `app/Views/admin/system/dashboard.php` | `DashboardController@index` | `components/admin_navbar.php`, `sidebar.php` | Administrative overview dashboard with Chart.js analytics. |
| `app/Views/admin/system/users.php` | `SystemController@users` | `components/admin_navbar.php`, `sidebar.php` | Institutional user account manager with granular RBAC permissions. |
| `app/Views/admin/system/user_activity.php` | `SystemController@userActivity` | `components/admin_navbar.php`, `sidebar.php` | User-specific chronological audit trail feed. |
| `app/Views/admin/system/audit_logs.php` | `SystemController@auditLogs` | `components/admin_navbar.php`, `sidebar.php` | System-wide immutable audit trail with JSON diff inspectors. |
| `app/Views/admin/system/backup.php` | `SystemController@backup` | `components/admin_navbar.php`, `sidebar.php` | MariaDB SQL database backup download and SQL restore upload console. |
| `app/Views/admin/system/settings.php` | `SystemController@settings` | `components/admin_navbar.php`, `sidebar.php` | Key-value system configuration editor (active SY, semester, unit rates). |
| `app/Views/admin/system/reports.php` | `ReportController@index` | `components/admin_navbar.php`, `sidebar.php` | Institutional demographics, revenue, and clearance analytics with CSV export. |
| `app/Views/admin/system/lms_course_generator.php` | `LmsAdminController@courseGenerator` | `components/admin_navbar.php`, `sidebar.php` | Automated shell generator converting timetable sections into active LMS courses. |

---

## 5. Learning Management System (LMS) Views (Student & Faculty)

### LMS Student Portal Views (15 Views)
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/lms/student/layout_header.php` | Include Partial | Header component | Student portal sidebar, navbar, and SPA navigation wrapper. |
| `app/Views/lms/student/layout_footer.php` | Include Partial | Footer component | Student portal script dependencies and closing markup. |
| `app/Views/lms/student/components/course_header.php` | Include Partial | Course partial | Course banner, professor details, and quick stats header. |
| `app/Views/lms/student/components/course_nav.php` | Include Partial | Course partial | Course sub-navigation tabs (Modules, Announcements, Assignments, Quizzes, Attendance, Gradebook). |
| `app/Views/lms/student/dashboard.php` | `StudentController@dashboard` | `layout_header.php`, `layout_footer.php` | Interactive student dashboard with study streak, upcoming deadlines, announcements. |
| `app/Views/lms/student/course.php` | `StudentController@course` | `layout_header.php`, `layout_footer.php` | Course homepage with collapsible module chapters and downloadable materials. |
| `app/Views/lms/student/my_courses.php` | `StudentController@myCourses` | `layout_header.php`, `layout_footer.php` | Card grid of all enrolled semester courses. |
| `app/Views/lms/student/profile.php` | `StudentController@profile` | `layout_header.php`, `layout_footer.php` | Student personal profile and academic credentials display. |
| `app/Views/lms/student/messages.php` | `StudentController@messages` | `layout_header.php`, `layout_footer.php` | Direct messaging interface between student and professors. |
| `app/Views/lms/student/calendar/index.php` | `StudentCalendarController@index` | `layout_header.php`, `layout_footer.php` | Interactive FullCalendar view synthesizing assignment deadlines and exam dates. |
| `app/Views/lms/student/gradebook/index.php` | `StudentGradebookController@index` | `layout_header.php`, `layout_footer.php` | Student term gradebook and weighted category score card. |
| `app/Views/lms/student/attendance/index.php` | `StudentAttendanceController@index` | `layout_header.php`, `layout_footer.php` | Course session attendance history and attendance compliance meter. |
| `app/Views/lms/student/announcements/index.php` | `StudentAnnouncementController@index` | `layout_header.php`, `layout_footer.php` | Course-specific announcements feed. |
| `app/Views/lms/student/assignments/index.php` | `StudentAssignmentController@index` | `layout_header.php`, `layout_footer.php` | Course assignment list with submission deadlines and status chips. |
| `app/Views/lms/student/assignments/show.php` | `StudentAssignmentController@show` | `layout_header.php`, `layout_footer.php` | Assignment prompt view with file upload drag-and-drop submission form. |
| `app/Views/lms/student/quizzes/index.php` | `StudentQuizController@index` | `layout_header.php`, `layout_footer.php` | Available course quizzes with attempt limits and scores. |
| `app/Views/lms/student/quizzes/show.php` | `StudentQuizController@show` | `layout_header.php`, `layout_footer.php` | Quiz instructions, time limit, and "Start Attempt" trigger. |
| `app/Views/lms/student/quizzes/attempt.php` | `StudentQuizController@attempt` | `layout_header.php`, `layout_footer.php` | Live timed quiz exam screen with timer countdown and auto-save. |
| `app/Views/lms/student/quizzes/result.php` | `StudentQuizController@result` | `layout_header.php`, `layout_footer.php` | Quiz attempt score summary, score breakdown, and pass/fail indicator. |

### LMS Faculty Portal Views (22 Views)
| View Template Path | Rendering Controller & Action | Layout Wrapper | Functional Purpose |
|---|---|---|---|
| `app/Views/lms/faculty/layout_header.php` | Include Partial | Header component | Faculty portal sidebar, navigation, and SPA wrapper. |
| `app/Views/lms/faculty/layout_footer.php` | Include Partial | Footer component | Faculty portal script bundles and closing tags. |
| `app/Views/lms/faculty/dashboard.php` | `FacultyController@dashboard` | `layout_header.php`, `layout_footer.php` | Faculty homepage showing active teaching courses and grading queues. |
| `app/Views/lms/faculty/course.php` | `FacultyController@course` | `layout_header.php`, `layout_footer.php` | Course management workspace with module creator and material uploader. |
| `app/Views/lms/faculty/profile.php` | `FacultyController@profile` | `layout_header.php`, `layout_footer.php` | Faculty profile and department credentials. |
| `app/Views/lms/faculty/messages.php` | `FacultyController@messages` | `layout_header.php`, `layout_footer.php` | Faculty communication inbox. |
| `app/Views/lms/faculty/calendar/index.php` | `FacultyCalendarController@index` | `layout_header.php`, `layout_footer.php` | Faculty teaching schedule and assignment deadline calendar. |
| `app/Views/lms/faculty/gradebook/index.php` | `FacultyGradebookController@index` | `layout_header.php`, `layout_footer.php` | Comprehensive class roster gradebook matrix. |
| `app/Views/lms/faculty/announcements/index.php` | `FacultyAnnouncementController@index` | `layout_header.php`, `layout_footer.php` | Published course broadcast announcements list. |
| `app/Views/lms/faculty/announcements/form.php` | `FacultyAnnouncementController@create` | `layout_header.php`, `layout_footer.php` | Broadcast announcement composer form. |
| `app/Views/lms/faculty/assignments/index.php` | `FacultyAssignmentController@index` | `layout_header.php`, `layout_footer.php` | Course assignments manager. |
| `app/Views/lms/faculty/assignments/form.php` | `FacultyAssignmentController@create` | `layout_header.php`, `layout_footer.php` | Assignment creator/editor with due date and max points settings. |
| `app/Views/lms/faculty/assignments/submissions.php` | `FacultyAssignmentController@submissions` | `layout_header.php`, `layout_footer.php` | Student submissions grading table with inline score and feedback inputs. |
| `app/Views/lms/faculty/attendance/index.php` | `FacultyAttendanceController@index` | `layout_header.php`, `layout_footer.php` | Course attendance sessions history table. |
| `app/Views/lms/faculty/attendance/form.php` | `FacultyAttendanceController@create` | `layout_header.php`, `layout_footer.php` | Session attendance roll-call form (`present`, `late`, `absent`, `excused`). |
| `app/Views/lms/faculty/attendance/edit.php` | `FacultyAttendanceController@edit` | `layout_header.php`, `layout_footer.php` | Edit existing attendance session records. |
| `app/Views/lms/faculty/quizzes/index.php` | `FacultyQuizController@index` | `layout_header.php`, `layout_footer.php` | Published and draft quizzes catalog. |
| `app/Views/lms/faculty/quizzes/form.php` | `FacultyQuizController@create` | `layout_header.php`, `layout_footer.php` | Quiz settings editor (time limit, passing score, attempts). |
| `app/Views/lms/faculty/quizzes/questions.php` | `FacultyQuizController@questions` | `layout_header.php`, `layout_footer.php` | Question builder (MCQ, True/False, Short Answer) and choices manager. |
| `app/Views/lms/faculty/quizzes/results.php` | `FacultyQuizController@results` | `layout_header.php`, `layout_footer.php` | Student quiz attempts results and score analytics. |

---

## 6. Shared Components & Email Templates

### Shared Reusable Partials (7 Views)
- `app/Views/components/header.php` — Master HTML5 document head, Bootstrap 5 CSS, Google Fonts, and meta headers.
- `app/Views/components/footer.php` — Master scripts bundle (Bootstrap 5 JS, SweetAlert2, Chart.js).
- `app/Views/components/navbar.php` — Public homepage navigation bar.
- `app/Views/components/admin_navbar.php` — Administrative portal top navbar with notifications dropdown and user profile pill.
- `app/Views/components/sidebar.php` — Administrative side navigation bar with role-filtered links.
- `app/Views/components/applicant_navbar.php` — Applicant self-service portal header and status banner.
- `app/Views/applicant/components/navbar.php` — Legacy applicant header partial.

### Branded HTML Email Templates (3 Views)
- `app/Views/emails/email_verification.php` — 6-digit registration OTP verification email template with branded TTU styling.
- `app/Views/emails/password_reset_otp.php` — 6-digit password reset OTP email template.
- `app/Views/emails/welcome_credentials.php` — Official matriculation welcome email delivering student ID, `@ttu.edu.ph` institutional email, and temporary password.

---
**Related:**
- [[00 - File Reference Index]]
- [[01 - Controllers Reference]]
- [[04 - Core & Middleware Reference]]
