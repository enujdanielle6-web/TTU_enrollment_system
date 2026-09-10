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
LmsService:
1. Query active assigned courses:
   SELECT lc.id as lms_course_id, lc.academic_level, lc.academic_section_id, lc.subject_id,
          s.subject_code, s.subject_name, s.units,
          COALESCE(cs.section_code, ss.section_code) as section_code,
          u.first_name, u.last_name, u.email,
          (SELECT COUNT(DISTINCT a.user_id) FROM applications a 
           WHERE a.section_id = lc.academic_section_id AND a.status IN ('enrolled', 'approved')) as enrolled_count
   FROM lms_courses lc
   JOIN subjects s ON lc.subject_id = s.id
   LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
   LEFT JOIN shs_sections ss ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id
   LEFT JOIN users u ON lc.faculty_user_id = u.id
   WHERE lc.faculty_user_id = :fid AND lc.status = 'active'
2. Query pending grading count:
   SELECT COUNT(*) FROM lms_submissions sub
   JOIN lms_assignments a ON sub.assignment_id = a.id
   JOIN lms_courses lc ON a.lms_course_id = lc.id
   WHERE lc.faculty_user_id = :fid AND sub.status IN ('SUBMITTED', 'RESUBMITTED')
3. Query recent student submissions (LIMIT 5)
4. Query recent course notices/announcements (LIMIT 5)
    ↓
Renders modern 2-column layout aligned with Student LMS:
- Welcome Hero banner with teaching metrics (Total Courses, Enrolled Students, To Grade)
- Quick Action shortcut cards (Teaching, Calendar, Messages, Profile)
- Course cards with vibrant gradients, academic level badges, units, section, enrolled counts
- Grading Queue and Course Notices side widgets
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

## 3. Assignment Authoring & Submission Grading (`/lms/faculty/course/{course_id}/assignments`)

### Page Identity
- **File Paths:**
  - Index: [`app/Views/lms/faculty/assignments/index.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/assignments/index.php)
  - Create: [`app/Views/lms/faculty/assignments/create.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/assignments/create.php)
  - Edit: [`app/Views/lms/faculty/assignments/edit.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/assignments/edit.php)
  - Submissions & Grading: [`app/Views/lms/faculty/assignments/submissions.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/assignments/submissions.php)
- **Controller:** [`app/Controllers/Lms/FacultyAssignmentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAssignmentController.php) (`index()`, `create()`, `store()`, `edit()`, `update()`, `submissions()`, `grade()`)
- **Routes:**
  - `GET /lms/faculty/course/{course_id}/assignments`
  - `GET /lms/faculty/course/{course_id}/assignments/create`
  - `POST /lms/faculty/course/{course_id}/assignments/store`
  - `GET /lms/faculty/course/{course_id}/assignments/{id}/edit`
  - `POST /lms/faculty/course/{course_id}/assignments/{id}/update`
  - `GET /lms/faculty/course/{course_id}/assignments/{id}/submissions`
  - `POST /lms/faculty/course/{course_id}/assignments/{id}/grade`

### Submission Grading Chain
```text
POST /lms/faculty/course/{course_id}/assignments/{id}/grade (submission_id, grade, feedback)
    ↓
FacultyAssignmentController@grade
    ↓
Validation: grade <= lms_assignments.max_points
    ↓
Database Operation:
    UPDATE lms_submissions SET grade = ?, feedback = ?, graded_at = NOW() WHERE id = ?
    ↓
Redirect: /lms/faculty/course/{course_id}/assignments/{id}/submissions with toast notification
```

---

## 4. Quiz Authoring & Question Bank Engine (`/lms/faculty/course/{course_id}/quizzes`)

### Page Identity
- **File Paths:**
  - Index: [`app/Views/lms/faculty/quizzes/index.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/quizzes/index.php)
  - Create: [`app/Views/lms/faculty/quizzes/create.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/quizzes/create.php)
  - Edit: [`app/Views/lms/faculty/quizzes/edit.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/quizzes/edit.php)
  - Question Bank: [`app/Views/lms/faculty/quizzes/questions.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/quizzes/questions.php)
  - Results Analysis: [`app/Views/lms/faculty/quizzes/results.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/quizzes/results.php)
- **Controller:** [`app/Controllers/Lms/FacultyQuizController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyQuizController.php) (`index()`, `create()`, `store()`, `edit()`, `update()`, `questions()`, `storeQuestion()`, `results()`)
- **Routes:**
  - `GET /lms/faculty/course/{course_id}/quizzes`
  - `GET /lms/faculty/course/{course_id}/quizzes/create`
  - `POST /lms/faculty/course/{course_id}/quizzes/store`
  - `GET /lms/faculty/course/{course_id}/quizzes/{id}/edit`
  - `POST /lms/faculty/course/{course_id}/quizzes/{id}/update`
  - `GET /lms/faculty/course/{course_id}/quizzes/{id}/questions`
  - `POST /lms/faculty/course/{course_id}/quizzes/{id}/questions/store`
  - `GET /lms/faculty/course/{course_id}/quizzes/{id}/results`

### Quiz Builder Tracing
```mermaid
flowchart TD
    Faculty[Faculty Instructor] -->|Create Quiz: title, duration, passing score| QForm[POST /lms/faculty/course/{c_id}/quizzes/store]
    QForm --> QController[FacultyQuizController@store]
    QController --> DB1[INSERT INTO lms_quizzes]
    DB1 --> QuestionUI[Redirect to /questions: Question Authoring Form]
    QuestionUI -->|Add Question & Choices| DB2[POST /questions/store -> INSERT INTO lms_questions]
    DB2 --> DB3[INSERT INTO lms_question_choices is_correct=1/0]
    DB3 --> Published[Quiz Ready for Students]
```

---

## 5. Attendance Session Logger (`/lms/faculty/course/{course_id}/attendance`)

### Page Identity
- **File Paths:**
  - Index: [`app/Views/lms/faculty/attendance/index.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/attendance/index.php)
  - Create Session: [`app/Views/lms/faculty/attendance/create.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/attendance/create.php)
  - Edit Session: [`app/Views/lms/faculty/attendance/edit.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty/attendance/edit.php)
- **Controller:** [`app/Controllers/Lms/FacultyAttendanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAttendanceController.php) (`index()`, `create()`, `store()`, `edit()`, `update()`)
- **Routes:**
  - `GET /lms/faculty/course/{course_id}/attendance`
  - `GET /lms/faculty/course/{course_id}/attendance/create`
  - `POST /lms/faculty/course/{course_id}/attendance/store`
  - `GET /lms/faculty/course/{course_id}/attendance/{id}/edit`
  - `POST /lms/faculty/course/{course_id}/attendance/{id}/update`

### Data Flow
Creates records in `lms_attendance_sessions` and loops through class roster to insert `lms_attendance_records` (`present`, `late`, `absent`, `excused`).

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[12 - LMS Student Portal Relationship Map]]
- [[08 - Scheduler Admin Relationship Map]]
