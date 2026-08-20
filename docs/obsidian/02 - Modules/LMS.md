# Learning Management System (LMS)

**Portal Paths**: `/lms/student/*` (Student) and `/lms/faculty/*` (Faculty)  
**Authentication**: Dedicated endpoints (`/auth/lms_student_login.php`, `/auth/lms_faculty_login.php`)  
**Repositories**: [`CollegeEnrollmentRepository.php`](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php), [`ShsEnrollmentRepository.php`](file:///c:/xampp/htdocs/sia/app/Repositories/ShsEnrollmentRepository.php)  
**Service**: [`LmsService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php)

The TTU Learning Management System (LMS) is a comprehensive online learning platform integrated into the enrollment system.

---

## 1. Dual Enrollment Integration (College + SHS)
The LMS is parasitic on the Enrollment System:
- **Enrollment Sources:**
  - **College Students:** Courses are derived from `college_enrollments` linked to active `applications` where `status = 'enrolled'`.
  - **Senior High School Students:** Courses are derived from `shs_enrollments` linked to active `applications`.
- **Dynamic Auto-Provisioning:** When a student logs in, the repository automatically provisions active records in `lms_courses` for every enrolled subject and maps the instructor from section advisers. If a subject is added or dropped by the Registrar, the LMS courses update dynamically.

---

## 2. Authentication & Separate Portal Access
- **Student Login (`/auth/lms_student_login.php`):** Students authenticate using their **Student Number** (`2026-000003`) and unified account password.
- **Faculty Login (`/auth/lms_faculty_login.php`):** Faculty authenticate using their Employee ID / Faculty ID.
- **Dedicated Logout Endpoints:**
  - `/auth/lms_student_logout.php` (or `/lms/student/logout`) securely terminates the session and redirects to the Student LMS Login.
  - `/auth/lms_faculty_logout.php` (or `/lms/faculty/logout`) redirects to the Faculty LMS Login.

---

## 3. Implemented LMS Subsystems & Features

### 3.1 Student Portal (`app/Controllers/Lms/`)
- **Dynamic Dashboard (`StudentController`):** Real-time course cards with custom gradients, instructor information, live deadlines counter, dynamic announcements, next scheduled class event, and active study streak tracker.
- **My Courses (`StudentController@myCourses`):** Interactive enrolled courses view.
- **Learning Modules (`StudentController@course`):** Downloadable module PDFs, lesson materials, and lecture notes.
- **Assignments (`StudentAssignmentController`):** Assignment instructions, deadlines, submission status, file upload, and faculty grade view.
- **Online Quizzes (`StudentQuizController`):** Timed multiple-choice/true-false quizzes, question navigation, answer submission, and immediate score feedback.
- **Gradebook (`StudentGradebookController`):** Grade breakdown across assignments, quizzes, and midterm/finals.
- **Attendance (`StudentAttendanceController`):** Attendance history, percentage, and present/absent logs.
- **Announcements & Calendar (`StudentAnnouncementController`, `StudentCalendarController`):** Course updates and timetable events.

### 3.2 Faculty Portal (`app/Controllers/Lms/`)
- **Faculty Dashboard (`FacultyController`):** Class rosters, active assigned sections, and pending grading queue.
- **Course Management:** Upload learning modules, lecture files, and syllabus documents.
- **Assignment Manager (`FacultyAssignmentController`):** Create/edit assignments, view student submissions, and submit grades with feedback.
- **Quiz Authoring Engine (`FacultyQuizController`):** Create timed quizzes, author question banks with choices, set correct answers, and review student attempt scores.
- **Gradebook & Attendance (`FacultyGradebookController`, `FacultyAttendanceController`):** Record class attendance sessions and manage overall course grades.

### 3.3 Secure File Delivery (`DownloadController`)
File downloads are routed through authenticated controller actions (`/lms/download/material/{id}` and `/lms/download/submission/{id}`) to prevent Insecure Direct Object Reference (IDOR) attacks.

---
**Related:**
- [[LMS_Database_Architecture]]
- [[LMS_Phase_2_Foundation]]
- [[LMS Profile and Messages UI]]
- [[ADR-003 Hybrid SPA Navigation Design]]
