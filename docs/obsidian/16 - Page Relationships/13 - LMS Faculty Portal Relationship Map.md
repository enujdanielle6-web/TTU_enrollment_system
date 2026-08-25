# LMS Faculty Portal Relationship Map

This document traces the complete code relationships, authoring engines, grading workflows, and database interactions for the Faculty LMS Portal.

---

## 1. Faculty LMS Dashboard (`/lms/faculty/dashboard.php`)

### Page Identity
- **File Path:** [`app/Views/lms/faculty/dashboard.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/dashboard.php)
- **Controller:** [`app/Controllers/Lms/FacultyController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php) (`dashboard()`)
- **Route:** `GET /lms/faculty/dashboard.php`
- **Authorized Role:** `faculty`
- **Middleware:** `SessionSecurityMiddleware`, `AuthMiddleware`, `RoleMiddleware:faculty`

### Database Tracing Chain
```text
GET /lms/faculty/dashboard.php
    ↓
FacultyController@dashboard
    ↓
1. Query active assigned courses:
   SELECT c.*, s.subject_code, s.subject_name, sec.section_code
   FROM lms_courses c
   JOIN subjects s ON c.subject_id = s.id
   LEFT JOIN college_sections sec ON c.college_section_id = sec.id
   WHERE c.instructor_id = ?
2. Query pending grading count:
   SELECT COUNT(*) as pending_count 
   FROM lms_submissions sub
   JOIN lms_assignments a ON sub.lms_assignment_id = a.id
   JOIN lms_courses c ON a.lms_course_id = c.id
   WHERE c.instructor_id = ? AND sub.grade IS NULL
    ↓
Renders assigned class roster cards, enrollment counts, and pending grading alerts
```

---

## 2. Course Content & Module Authoring (`/lms/faculty/course.php`)

### Page Identity
- **File Path:** [`app/Views/lms/faculty/course.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/course.php)
- **Controller:** `FacultyController@course`, `FacultyController@saveModule`, `FacultyController@uploadMaterial`
- **Routes:** `GET /lms/faculty/course.php?id={id}`, `POST /lms/faculty/module_save.php`, `POST /lms/faculty/material_upload.php`

### Material Upload & Storage Chain
```text
POST /lms/faculty/material_upload.php (module_id, title, description, material_file)
    ↓
FacultyController@uploadMaterial
    ↓
Validation: File extension check (PDF, DOCX, PPTX, MP4, ZIP <= 50MB)
    ↓
Store file: `storage/lms_materials/{unique_id}_{filename}`
    ↓
Database Operation:
    INSERT INTO lms_materials (lms_module_id, title, description, file_path, file_type, status)
    VALUES (?, ?, ?, ?, ?, 'published')
    ↓
Redirect: /lms/faculty/course.php?id={course_id}
```

---

## 3. Assignment Authoring & Submission Grading (`/lms/faculty/assignments.php`)

### Page Identity
- **File Path:** [`app/Views/lms/faculty/assignments.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/assignments.php)
- **Controller:** [`app/Controllers/Lms/FacultyAssignmentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAssignmentController.php) (`index()`, `create()`, `grade()`)
- **Routes:** `GET /lms/faculty/assignments.php`, `POST /lms/faculty/assignment_create.php`, `POST /lms/faculty/grade_submission.php`

### Submission Grading Chain
```text
POST /lms/faculty/grade_submission.php (submission_id, grade, feedback)
    ↓
FacultyAssignmentController@grade
    ↓
Validation: grade <= lms_assignments.max_points
    ↓
Database Operation:
    UPDATE lms_submissions SET grade = ?, feedback = ?, graded_at = NOW() WHERE id = ?
    ↓
Redirect: /lms/faculty/assignments.php with toast notification
```

---

## 4. Quiz Authoring & Question Bank Engine (`/lms/faculty/quizzes.php`)

### Page Identity
- **File Path:** [`app/Views/lms/faculty/quizzes.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/quizzes.php)
- **Controller:** [`app/Controllers/Lms/FacultyQuizController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyQuizController.php) (`index()`, `createQuiz()`, `addQuestion()`)

### Quiz Builder Tracing
```mermaid
flowchart TD
    Faculty[Faculty Instructor] -->|Create Quiz: title, duration, passing score| QForm[POST /lms/faculty/quiz_create.php]
    QForm --> QController[FacultyQuizController@createQuiz]
    QController --> DB1[INSERT INTO lms_quizzes]
    DB1 --> QuestionUI[Interactive Question Authoring Modal]
    QuestionUI -->|Add Question & Choices| DB2[INSERT INTO lms_questions]
    DB2 --> DB3[INSERT INTO lms_question_choices is_correct=1/0]
    DB3 --> Published[Quiz Ready for Students]
```

---

## 5. Attendance Session Logger (`/lms/faculty/attendance.php`)

### Page Identity
- **File Path:** [`app/Views/lms/faculty/attendance.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/attendance.php)
- **Controller:** [`app/Controllers/Lms/FacultyAttendanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAttendanceController.php) (`index()`, `saveSession()`)

### Data Flow
Creates records in `lms_attendance_sessions` and loops through class roster to insert `lms_attendance_records` (`present`, `late`, `absent`, `excused`).

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[12 - LMS Student Portal Relationship Map]]
- [[08 - Scheduler Admin Relationship Map]]
