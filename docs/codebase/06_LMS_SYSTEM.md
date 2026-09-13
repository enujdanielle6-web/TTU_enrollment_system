# 06. LMS SYSTEM

## Subsystem Architecture
The TTU Learning Management System facilitates day-to-day academic interaction for students and faculty.

### 1. Dedicated Authentication
- Routes: `/sia/auth/lms_student_login.php` (Student ID) and `/sia/auth/lms_faculty_login.php` (Employee ID).
- Controller: `App\Controllers\Lms\LmsAuthController`.
- Enforces active student enrollment before granting dashboard access.

### 2. Courses & Section Binding
- Entity: `lms_courses` links a subject, an academic section, and a faculty user.
- Data Retrieval: `LmsService::getStudentCourses()` and `LmsService::getFacultyCourses()`.

### 3. Modules & Learning Materials
- `FacultyController::createModule`: Groups resources into sequential course units (`lms_modules`).
- `FacultyController::uploadMaterial`: Attaches downloadable course documents (`lms_materials`).
- `DownloadController`: Streams materials securely to enrolled students.

### 4. Assignment Submissions & Grading
- `FacultyAssignmentController`: Creates assignments with instructions, points, and deadlines.
- `StudentAssignmentController::submit`: Handles student file uploads into `app/uploads/lms/submissions/`.
- `FacultyAssignmentController::grade`: Records points awarded and written feedback.

### 5. Timed Quiz Engine
- `LmsQuizService`: Manages question banks (Multiple Choice, True/False, Short Answer).
- `StudentQuizController`: Renders timed quiz interface and records answer submissions within a database transaction.

### 6. Gradebook Computation
- `LmsGradebookService`: Calculates weighted averages across assignment, quiz, exam, and attendance categories.
- Translates percentage grades into the Philippine collegiate 1.00–5.00 GPA grading scale.

### 7. Attendance Tracking
- `FacultyAttendanceController`: Opens daily attendance sessions and logs roll call (`present`, `late`, `absent`, `excused`).
- `StudentAttendanceController`: Displays personal attendance metrics and history.
