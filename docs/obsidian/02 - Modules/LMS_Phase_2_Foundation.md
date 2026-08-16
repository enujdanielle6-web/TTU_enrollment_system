# TTU LMS Phase 2 - Foundation Planning

## 1. Development Order

To build a robust LMS foundation without disrupting the enrollment system, development will proceed in the following order:

1. **Database Expansion**: Create new LMS-specific tables (Modules, Lessons, Materials, Progress) with foreign keys to existing `users`, `subjects`, and `college_sections` tables.
2. **Course Overview (Student)**: Implement `course.php` to serve as the landing page for an individual course, displaying instructor info and syllabus/overview.
3. **Faculty Class Management**: Build the interface for instructors to select their assigned classes/sections.
4. **Module & Lesson Builder (Faculty)**: Create the UI for instructors to define modules (e.g., "Week 1", "Chapter 1") and add lessons underneath them.
5. **Material Upload System (Faculty)**: Implement secure file uploading for PDFs, slides, and videos attached to specific lessons.
6. **Student Lesson Viewer**: Build the interface where students consume the learning materials (video player, PDF viewer, rich text).
7. **Progress Tracking**: Implement the logic to mark lessons as complete and calculate course completion percentages.
8. **Dashboard Enhancements**: Update the student dashboard to reflect real progress and replace hardcoded widgets.

---

## 2. Feature Dependencies

* **Course Overview** depends on: `subjects`, `college_sections`, `users` (Faculty).
* **Module Builder** depends on: Course Overview (Faculty must select a course first).
* **Material Uploads** depends on: Modules/Lessons existing to attach files to.
* **Student Lesson Viewer** depends on: Material Uploads and Modules.
* **Progress Tracking** depends on: Student Lesson Viewer (tracking when a student finishes viewing a material).
* **Dashboard Improvements** depends on: Progress Tracking (to show real completion data).

---

## 3. Database Tables Needed (3NF)

The LMS will store *only* learning data. All enrollment and user identity data remains in the main system.

* **`lms_modules`**
  * `id` (PK)
  * `subject_id` (FK to `subjects`)
  * `college_section_id` (FK to `college_sections`) - *Optional, if modules differ by section*
  * `teacher_id` (FK to `users`)
  * `title` (VARCHAR)
  * `description` (TEXT)
  * `sequence` (INT) - *For ordering*
  * `created_at` / `updated_at`

* **`lms_lessons`**
  * `id` (PK)
  * `module_id` (FK to `lms_modules`)
  * `title` (VARCHAR)
  * `content` (TEXT) - *Rich text content*
  * `sequence` (INT)
  * `created_at` / `updated_at`

* **`lms_materials`**
  * `id` (PK)
  * `lesson_id` (FK to `lms_lessons`)
  * `title` (VARCHAR)
  * `file_path` (VARCHAR)
  * `file_type` (VARCHAR) - *e.g., pdf, video, docx*
  * `created_at`

* **`lms_student_progress`**
  * `id` (PK)
  * `student_id` (FK to `users`)
  * `lesson_id` (FK to `lms_lessons`)
  * `status` (ENUM: 'in_progress', 'completed')
  * `completed_at` (TIMESTAMP)

---

## 4. Folder Structure

```text
sia/
└── lms/
    ├── student/
    │   ├── course.php            (New - Course overview)
    │   ├── lesson.php            (New - Lesson viewer)
    │   ├── api_mark_complete.php (New - Progress tracking endpoint)
    ├── faculty/
    │   ├── classes.php           (New - Class management)
    │   ├── modules.php           (New - Module builder)
    │   ├── lessons.php           (New - Lesson builder)
    │   ├── upload_material.php   (New - File handler)
    ├── uploads/
    │   └── materials/            (New - Secure directory for files)
    └── components/               (New - Shared LMS UI components)
```

---

## 5. Files to Create

1. **`database/lms_phase2.sql`**: Migration script for the new tables.
2. **`lms/student/course.php`**: The hub for a specific course (syllabus, module list).
3. **`lms/student/lesson.php`**: The UI for consuming materials and rich text.
4. **`lms/faculty/classes.php`**: Lists sections assigned to the faculty member.
5. **`lms/faculty/modules.php`**: CRUD interface for Modules.
6. **`lms/faculty/lessons.php`**: CRUD interface for Lessons and file attachments.
7. **`lms/faculty/upload_material.php`**: Backend script to handle file validation and storage.

---

## 6. Files to Modify

1. **`lms/student/dashboard.php`**: Update Quick Actions to point to real links; update "My Courses" loop to link to the new `course.php`.
2. **`lms/student/my_courses.php`**: Ensure links point to `course.php`.
3. **`lms/faculty/dashboard.php`**: Remove the "Phase 2" placeholder; replace with actual metrics (active classes, recent uploads).
4. **`lms/student/layout_header.php`**: Update active states for the new course and lesson pages.

---

## 7. Risks

* **File Storage Scalability**: Uploading videos directly to the server can quickly consume disk space. (Mitigation: Enforce strict file size limits; consider cloud storage for videos later).
* **Data Duplication**: Accidentally duplicating course/subject definitions instead of linking to `subjects`. (Mitigation: Strict adherence to FK constraints against the main schema).
* **Security (File Uploads)**: Malicious file uploads via the materials feature. (Mitigation: Strict MIME-type checking, renaming files on upload, disabling execution in the `uploads/materials` folder).

---

## 8. Validation Rules

* **Database**: `ON DELETE CASCADE` must be used so that deleting a module deletes its lessons, and deleting a lesson deletes its materials.
* **Enrollment**: A student can *only* access `course.php` if a valid record exists in `college_enrollments` for their `user_id` and that `subject_id`.
* **Faculty Access**: A faculty member can *only* modify modules for a `subject_id` they are actively assigned to.
* **File Uploads**: Max file size = 50MB. Allowed extensions = `.pdf, .docx, .pptx, .mp4`.

---

## 9. Milestones

* **Milestone 1**: Database Schema deployed and `course.php` (read-only) created.
* **Milestone 2**: Faculty Module & Lesson Builder completed.
* **Milestone 3**: Secure Material Upload system functional.
* **Milestone 4**: Student Lesson Viewer and Progress Tracking completed.
* **Milestone 5**: Dashboard and Navigation fully wired up with dynamic data.

---

## 10. Rollback Strategy

1. **Database Rollback**: A `rollback_lms_phase2.sql` script will be prepared to `DROP` the new tables (`lms_materials`, `lms_lessons`, `lms_modules`, `lms_student_progress`).
2. **Code Rollback**: Since we are primarily adding *new* files, rolling back involves deleting the new files in `lms/student/` and `lms/faculty/`.
3. **Dashboard Reversion**: Maintain a backup of the current `dashboard.php` with the hardcoded widgets to restore if the dynamic widgets fail.
