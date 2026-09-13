# 07. ENROLLMENT ↔ LMS INTEGRATION

## Integration Architecture & Data Flow
The Enrollment System and LMS operate on a shared MariaDB database with both tightly coupled and decoupled data relationships.

### 1. Shared User Directory
- Students and Faculty share the `users` table.
- Students use `student_number` (assigned during enrollment finalization).
- Faculty use `users.role = 'faculty'` and have their employee identifier stored in `student_number`.

### 2. Section to Course Mapping
- Enrollment manages sections via `college_sections` and `shs_sections`.
- LMS binds to sections via `lms_courses.academic_section_id`.
- The reference is logical; there is no database-level foreign key constraint between `lms_courses` and section tables.

### 3. The Broken Faculty Scheduling Pipeline
1. **Account Creation Gap:** `SystemController::processUser` role whitelist omits `'faculty'`, preventing admins from creating faculty accounts.
2. **Scheduler Disconnect:** `SchedulerController` stores instructors as raw strings (`VARCHAR(150)` in `college_section_subjects.instructor`) instead of foreign keys to `users.id`.
3. **No Automatic Sync:** Schedulers publishing section timetables does not trigger `lms_courses` creation.
4. **Manual Admin Course Generator:** `LmsAdminController::courseGenerator` requires an administrator to manually pair unmapped section subjects with active faculty users.

### 4. The JIT Auto-Provisioning Fallback Hazard
When a student logs in to the LMS and accesses `/sia/lms/student/dashboard.php`:
- If an `lms_courses` record is missing for an enrolled section subject, `CollegeEnrollmentRepository` and `ShsEnrollmentRepository` execute an inline `INSERT INTO lms_courses`.
- The course is automatically assigned to whichever faculty user has the lowest ID in `users`, or to hardcoded ID `18`.
- This violates Command-Query Separation and crashes with an uncaught FK exception if no faculty account exists.
