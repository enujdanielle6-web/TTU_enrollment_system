# Learning Management System (LMS)

The LMS is a new module added to the TTU Enrollment System to allow students to access course materials, assignments, and grades, and for faculty to manage their classes.

## Features (16-Module Architecture)
1. **Authentication**: Login, Forgot Password, Profile Management, 2FA (Optional).
2. **Dashboard**: Welcome Page, Current Semester, Enrolled Courses, Upcoming Activities, Announcements, Calendar, Recent Grades, Progress Overview.
3. **My Courses**: View Enrolled Courses, Course Overview, Course Materials, Instructor Information, Course Progress.
4. **Learning Materials**: Modules, Lessons, PDFs, PPTs, Videos, External Resources, Downloadable Content.
5. **Assignments**: View Assignments, Instructions, Due Dates, Upload Submission, Resubmit, Submission History.
6. **Online Quizzes**: Available Quizzes, Timed Exams, Multiple Choice, True/False, Identification, Essay, Auto-save, Instant Results.
7. **Grades**: Activity Scores, Quiz Scores, Assignment Scores, Midterm/Final Grades, Grade Breakdown.
8. **Attendance**: Attendance Record, Percentage, Present/Absent Status.
*(Additional modules as per full specification)*

## Database Integration & Schema Notes
The LMS integrates directly with the main enrollment database (`sia`).
During development, note the following non-standard column names in the existing schema that are critical for LMS queries:

- **`subjects` Table:**
  - Code column: `subject_code` (NOT `code`)
  - Name column: `subject_name` (NOT `name`)
- **`college_sections` Table:**
  - Name column: `section_code` (NOT `section_name` or `name`)

### Key LMS Tables (New)
- `lms_modules`: Stores file paths and descriptions for course materials.
- `lms_assignments`: Stores assignment details, due dates, and max points.
- `lms_submissions`: Stores student file uploads and faculty grades for assignments.

## Authentication Flow
- The LMS shares the `users` table.
- Students log in via `auth/lms_student_login.php`. The backend verifies they have an active enrollment record in `college_enrollments` via their `application_id`.
- Faculty log in via `auth/lms_faculty_login.php` (Requires `role` = 'faculty').
