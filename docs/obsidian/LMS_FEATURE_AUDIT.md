# LMS Feature Audit & Operational Assessment

> **Audit Standard**: Every feature is classified under one of six authoritative operational states based on direct inspection of source code and live database execution:  
> 1. `IMPLEMENTED` — Fully working in code, UI, and database.  
> 2. `PARTIALLY IMPLEMENTED` — Works for specific cases but incomplete or missing critical edge cases.  
> 3. `BROKEN` — Code exists but fails at runtime due to bugs, exceptions, or path traversal issues.  
> 4. `MISSING` — Necessary capability completely absent from code and UI.  
> 5. `UNUSED` — Code or database structures present but never called or utilized.  
> 6. `DOCUMENTATION ONLY` — Claimed in Obsidian or developer handoff documents but does not exist in code.  

---

## 1. Student Portal Features

| Subsystem / Feature | Classification | Technical Evidence & Code References | Operational Analysis |
| :--- | :--- | :--- | :--- |
| **Student Dashboard** | `IMPLEMENTED` | [StudentController.php:13-27](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php#L13-L27)<br>[app/Views/lms/student/dashboard.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/dashboard.php) | Dynamic course cards with gradient covers, deadlines countdown, streak calculation, next scheduled event, and professor notice feed. |
| **Enrolled Courses List** | `IMPLEMENTED` | [StudentController.php:102-129](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php#L102-L129)<br>[app/Views/lms/student/my_courses.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/my_courses.php) | Full course matrix showing units, section badge, scheduled timetable blocks, and course shell links. |
| **Course Subject Overview** | `IMPLEMENTED` | [StudentController.php:29-100](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php#L29-L100)<br>[app/Views/lms/student/course.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/course.php) | Shows course code, title, section, instructor dossier, module accordions, and navigation tabs. |
| **Course Materials Download** | `BROKEN` | [DownloadController.php:21-48](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php#L21-L48)<br>[FacultyController.php:90](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php#L90) | **CRITICAL BUG**: (1) Checks non-existent `$_SESSION['role']` instead of `$_SESSION['user_role']`, throwing 403 Forbidden. (2) Material files uploaded by faculty were written to `c:\xampp\storage\lms_materials/` while download controller reads from `app/uploads/lms/`, causing 404 Not Found. |
| **Announcements Feed** | `IMPLEMENTED` | [StudentAnnouncementController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAnnouncementController.php)<br>[app/Views/lms/student/announcements/index.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/announcements/index.php) | Lists active course announcements filtered by `status = 'published'` and unexpired timestamp. |
| **Assignments View** | `IMPLEMENTED` | [StudentAssignmentController.php:28-47](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAssignmentController.php#L28-L47)<br>[app/Views/lms/student/assignments/index.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/assignments/index.php) | Lists assignments with due date badges, points possible, and current student submission status. |
| **Assignment Submission** | `IMPLEMENTED` | [StudentAssignmentController.php:74-132](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAssignmentController.php#L74-L132)<br>[app/Views/lms/student/assignments/show.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/assignments/show.php) | Secure multipart upload to `app/uploads/lms/submissions/`, handles initial submissions and resubmissions. |
| **Submission File Download** | `BROKEN` | [DownloadController.php:89-128](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php#L89-L128) | Fails with 403 Forbidden because `$role = $_SESSION['role'] ?? ''` evaluates to empty string. |
| **Online Quizzes** | `IMPLEMENTED` | [StudentQuizController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentQuizController.php)<br>[app/Views/lms/student/quizzes/](file:///c:/xampp/htdocs/sia/app/Views/lms/student/quizzes/) | Timed multiple-choice & true/false quizzes with client-side countdown timer, server-side grace period, auto-grading, and instant score breakdown. |
| **Gradebook** | `PARTIALLY IMPLEMENTED` | [StudentGradebookController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentGradebookController.php)<br>[LmsGradebookService.php:140-159](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php#L140-L159) | Functional presentation, but calculates the entire course gradebook across all students on every single student page load ($O(N \times M)$ query explosion). |
| **Attendance History** | `IMPLEMENTED` | [StudentAttendanceController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAttendanceController.php)<br>[app/Views/lms/student/attendance/index.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/attendance/index.php) | Clean attendance metrics gauge, risk badge, and chronological history log. |
| **Academic Calendar** | `IMPLEMENTED` | [StudentCalendarController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentCalendarController.php)<br>[LmsCalendarService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsCalendarService.php) | Monthly grid plotting assignment deadlines and quiz end dates across all enrolled courses. |
| **Student Profile** | `IMPLEMENTED` | [StudentController.php:131-135](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php#L131-L135)<br>[app/Views/lms/student/profile.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/profile.php) | User profile details view with enrolled program and student number. |
| **Messages / Forums** | `UNUSED` | [StudentController.php:137-141](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php#L137-L141)<br>[app/Views/lms/student/messages.php](file:///c:/xampp/htdocs/sia/app/Views/lms/student/messages.php) | View renders static UI mockup. No backend message storage or chat model exists. |
| **Course Access Restrictions** | `PARTIALLY IMPLEMENTED` | [CollegeEnrollmentRepository.php:40](file:///c:/xampp/htdocs/sia/app/Repositories/CollegeEnrollmentRepository.php#L40)<br>[LmsAuthController.php:96](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/LmsAuthController.php#L96) | Multi-section isolation works cleanly. However, access gating improperly allows `status = 'approved'` applicants into student login. |

---

## 2. Faculty Portal Features

| Subsystem / Feature | Classification | Technical Evidence & Code References | Operational Analysis |
| :--- | :--- | :--- | :--- |
| **Faculty Dashboard** | `PARTIALLY IMPLEMENTED` | [FacultyController.php:13-32](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php#L13-L32)<br>[app/Views/lms/faculty/dashboard.php](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/dashboard.php) | Renders teaching cards and pending submissions. However, enrolled student counter queries `applications.section_id`, hiding irregular students. |
| **Assigned Classes Roster** | `IMPLEMENTED` | [FacultyController.php:34-52](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php#L34-L52) | Faculty can only access course IDs where `faculty_user_id` matches their user ID (or admin override). |
| **Module Management** | `IMPLEMENTED` | [FacultyController.php:54-73](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php#L54-L73) | Modules can be created with ordering index and attached to course shells. |
| **Material Upload** | `BROKEN` | [FacultyController.php:75-111](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php#L75-L111) | Path traversal escape: writes files to `c:\xampp\storage\lms_materials/`. Incompatible with `DownloadController`. |
| **Assignment Authoring** | `IMPLEMENTED` | [FacultyAssignmentController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAssignmentController.php)<br>[app/Views/lms/faculty/assignments/](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/assignments/) | Full CRUD (create, read, edit, update) for course assignments with published/draft states. |
| **Submission Review & Grading** | `IMPLEMENTED` | [FacultyAssignmentController.php:111-149](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAssignmentController.php#L111-L149) | Faculty can review submissions, input decimal scores, and provide feedback remarks. |
| **Quiz Authoring Engine** | `IMPLEMENTED` | [FacultyQuizController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyQuizController.php)<br>[app/Views/lms/faculty/quizzes/](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/quizzes/) | Comprehensive quiz builder with time limits, passing scores, question bank, and true/false or multiple-choice options. |
| **Quiz Results Review** | `IMPLEMENTED` | [FacultyQuizController.php:180-200](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyQuizController.php#L180-L200) | Review all student attempt scores sorted by highest mark. |
| **Gradebook Grid** | `PARTIALLY IMPLEMENTED` | [FacultyGradebookController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyGradebookController.php)<br>[LmsGradebookService.php:58-135](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php#L58-L135) | Full grid view combining assignments and quizzes. However, query does not filter `applications.status = 'enrolled'`, pulling unapproved or dropped applicants. |
| **Attendance Session Logger** | `IMPLEMENTED` | [FacultyAttendanceController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAttendanceController.php)<br>[LmsAttendanceService.php](file:///c:/xampp/htdocs/sia/app/Services/LmsAttendanceService.php) | Creates sessions and records roster marks (`present`, `absent`, `late`, `excused`) in atomic transaction. |
| **Faculty Calendar** | `PARTIALLY IMPLEMENTED` | [FacultyCalendarController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyCalendarController.php)<br>[LmsCalendarService.php:81](file:///c:/xampp/htdocs/sia/app/Services/LmsCalendarService.php#L81) | Calendar displays events, but URLs hardcode student `/lms/student/...` links instead of faculty editing routes. |
| **Faculty Announcements** | `IMPLEMENTED` | [FacultyAnnouncementController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAnnouncementController.php) | Create, edit, and publish course bulletins with expiration dates. |
| **Faculty Messages** | `UNUSED` | [FacultyController.php:119-123](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php#L119-L123) | Mock view only. |

---

## 3. LMS Administrative Features

| Subsystem / Feature | Classification | Technical Evidence & Code References | Operational Analysis |
| :--- | :--- | :--- | :--- |
| **LMS Admin Dashboard** | `MISSING` | [sysadmin_dashboard.php](file:///c:/xampp/htdocs/sia/app/Views/admin/system/sysadmin_dashboard.php) | No dedicated LMS administrative dashboard exists. Admins only have general system metrics. |
| **Course Shell Generator** | `IMPLEMENTED` | [LmsAdminController.php](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php)<br>[app/Views/admin/system/lms_course_generator.php](file:///c:/xampp/htdocs/sia/app/Views/admin/system/lms_course_generator.php) | Pairs unmapped timetable section-subjects with active faculty to deploy `lms_courses` shells. |
| **Course Management / Index** | `MISSING` | None | Admins cannot view a list of all active LMS courses, edit assigned faculty, or archive/delete old courses. |
| **LMS User Management** | `MISSING` | None | No administrative panel to view student/faculty LMS activity, toggle `lms_status`, or lift academic holds. |
| **Enrollment Synchronization** | `MISSING` | None | No tool to force batch sync of section changes, irregular enrollments, or registrar finalizations. |
| **Academic Term Archival** | `MISSING` | None | No tool to archive course shells at the end of a semester or clone standardized syllabus modules. |
| **LMS Monitoring & Audit Logs** | `MISSING` | None | Submissions, grading events, and quiz attempts do not record audit entries in `activity_logs`. |

---

## 4. Documentation Discrepancies (Features Documented but Absent)

| Documented Feature | Location in Existing Documentation | Actual Implementation Status |
| :--- | :--- | :--- |
| **Lesson Hierarchy (`lms_lessons`)** | `docs/obsidian/02 - Modules/LMS_Database_Architecture.md` | `DOCUMENTATION ONLY` — `lms_lessons` does not exist in the database or codebase. Schema is 2-tier (`lms_modules` $\rightarrow$ `lms_materials`). |
| **Global Course Resources (`lms_course_resources`)** | `docs/obsidian/02 - Modules/LMS_Database_Architecture.md` | `DOCUMENTATION ONLY` — Table does not exist. |
| **Lesson Progress Tracker (`lms_student_progress`)** | `docs/obsidian/02 - Modules/LMS_Phase_2_Foundation.md` | `DOCUMENTATION ONLY` — Table does not exist. Progress is calculated from task submissions. |
| **Faculty Role Route Guard** | `docs/obsidian/16 - Page Relationships/13 - LMS Faculty Portal Relationship Map.md` | `DOCUMENTATION MISMATCH` — Claims `RoleMiddleware:faculty` protects `/lms/faculty/*`. In reality, only `AuthMiddleware` is applied in `web.php`. |
