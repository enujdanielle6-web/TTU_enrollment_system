# TTU LMS PHASE 3 STUDENT LMS COMPLETION SPECIFICATION

> **Subsystem**: Student Learning Management System (LMS)  
> **Phase**: Phase 3 — Student LMS Completion  
> **Status**: Completed & Verified (20/20 Automated Verification Tests Passed; 0 Regressions in Phase 1 & 2)  
> **Implementation Date**: September 30, 2026  
> **Authoritative Root**: `c:\xampp\htdocs\sia`  

---

## 1. ARCHITECTURAL OVERVIEW & DATA FLOW

Phase 3 finalizes and stabilizes the student-facing experience within the TTU LMS without modifying the Enrollment $\to$ LMS derivation established in Phases 1 and 2. 

### Student Access & Resolution Flow:
```text
┌────────────────────────────────────────────────────────┐
│                   Student Authentication               │
│                   Role: 'student'                      │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│               Enrollment Verification                  │
│       `applications.status` = 'enrolled'               │
│          AND active `academic_term`                    │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│               Official Enrolled Subjects               │
│    - College: `college_enrollments.status` = 'enrolled'│
│    - SHS: `shs_enrollments.status` = 'enrolled'        │
│    (Excludes 'dropped', 'withdrawn', or un-enrolled)   │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│                   LMS Course Shell                     │
│               `lms_courses.id`                         │
│       Bound to: (academic_level, section, subject)     │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│               Student Course Experience                │
│    - Modules & Permitted Materials Download            │
│    - Assignments & Secure File Submissions             │
│    - Timed Quizzes & Secure Question Submissions       │
│    - Personal Isolated Gradebook (O(1) Retrieval)      │
│    - Course Bulletins & Attendance Summary             │
└────────────────────────────────────────────────────────┘
```

---

## 2. STUDENT COURSE ACCESS & DYNAMIC DERIVATION

### 2.1 Dynamic Course Resolution Without Duplicate Tables
Rather than creating an artificial `lms_student_enrollments` table that could drift from registrar records, the Student LMS queries active academic registrations directly:
- **College Students**: Joined via `college_enrollments ce` where `ce.user_id = :uid AND ce.status = 'enrolled'`.
- **SHS Students**: Joined via `shs_enrollments se` where `se.student_id = :uid AND se.status = 'enrolled'`.
- **Controller Enforcement**: In [`app/Controllers/Lms/StudentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php) (`myCourses()`):
  ```sql
  WHERE a.user_id = :uid AND a.status = 'enrolled'
  ```

### 2.2 Lifecycle State Access Resolution
1. **Enrolled Student**: Successfully accesses LMS dashboard and active courses.
2. **Approved-but-not-enrolled Applicant**: Has an approved application (`status = 'approved'`) but not enrolled (`status != 'enrolled'`). Blocked at authentication / dashboard resolution.
3. **Dropped Subject**: When a student drops a subject, its status in `college_enrollments` / `shs_enrollments` updates to `'dropped'` with a `dropped_at` timestamp. It immediately drops from active course cards and `isStudentAuthorizedForCourse()` returns `false`.
4. **Withdrawn Subject**: Status changes to `'withdrawn'`. Immediately revokes access to learning materials, quizzes, and assignments while preserving prior grades/submissions.
5. **Section Transfer**: When registrar transfers a student from Section A to Section B, their `college_section_id` updates atomically. Access to Section A course shells is revoked, and access to Section B course shells is granted immediately.
6. **Irregular Enrollment**: Irregular students who take subjects across non-standard sections are evaluated on each subject's explicit section binding, accurately granting them access only to their specific assigned course shells.

---

## 3. STUDENT DASHBOARD

- **Controller**: [`app/Controllers/Lms/StudentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentController.php) (`index()`, `myCourses()`)
- **View**: [`app/Views/lms/student/dashboard.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/dashboard.php)
- **Features**:
  - Displays only actively enrolled LMS courses for the student's current academic term.
  - Presents subject code, subject title, section name, schedule details, and instructor.
  - **Unassigned Faculty State**: If a course has no assigned instructor (`faculty_user_id IS NULL`), the dashboard renders `"Instructor TBA"` cleanly without throwing null pointer warnings or broken avatars.

---

## 4. COURSE VIEW & SERVER-SIDE AUTHORIZATION

- **Controller**: [`app/Controllers/Lms/StudentCourseController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentCourseController.php) (`show()`)
- **View**: [`app/Views/lms/student/courses/show.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/courses/show.php)
- **Server-Side Authorization Check**:
  Every student course endpoint executes [`LmsService::isStudentAuthorizedForCourse($lmsCourseId, $studentId)`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php).
- **Protection**:
  If a student attempts to open an unauthorized course ID (whether belonging to another section, another academic level, or another student) via URL parameter tampering, they are immediately redirected or denied with a 403 Forbidden flash notification.

---

## 5. MODULES & LEARNING MATERIALS

- **Controllers**:
  - Module Listing: [`app/Controllers/Lms/StudentModuleController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentModuleController.php)
  - Secure File Download: [`app/Controllers/Lms/DownloadController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php)
- **Storage Architecture**:
  - Canonical base directory: `storage/uploads/lms/materials/`
  - DownloadController validates course authorization, checks real file existence on disk, and enforces binary stream transfer (`Content-Disposition: attachment`).
- **Security Defenses**:
  - Raw filesystem paths are never returned to the browser or embedded in links.
  - File retrieval uses basename normalization, preventing path traversal attacks (`../`).
  - Missing or deleted disk files return safe user-friendly flash errors instead of uncaught PHP exceptions or leaking physical directories.

---

## 6. ASSIGNMENTS & SUBMISSION WORKFLOW

- **Controller**: [`app/Controllers/Lms/StudentAssignmentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAssignmentController.php)
- **Service**: [`app/Services/LmsService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php)
- **Views**:
  - Listing: [`app/Views/lms/student/assignments/index.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/assignments/index.php)
  - Detail & Submission: [`app/Views/lms/student/assignments/show.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/assignments/show.php)

### 6.1 Resubmission Fix & Idempotency
- **Problem**: Previously, submitting an assignment created an `INSERT INTO lms_assignment_submissions` query. Because the table enforces a unique index on `(lms_assignment_id, student_id)`, any student attempt to resubmit threw an unhandled database exception (`PDOException: Duplicate entry for key 'idx_assignment_student'`).
- **Solution**: Refactored [`LmsService::submitAssignment()`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) to use `ON DUPLICATE KEY UPDATE`:
  ```sql
  INSERT INTO lms_assignment_submissions 
      (lms_assignment_id, student_id, file_path, submission_text, submitted_at, status)
  VALUES 
      (:aid, :sid, :file_path, :text, CURRENT_TIMESTAMP, 'submitted')
  ON DUPLICATE KEY UPDATE
      file_path = VALUES(file_path),
      submission_text = VALUES(submission_text),
      submitted_at = CURRENT_TIMESTAMP,
      status = 'resubmitted'
  ```

### 6.2 Submission Hardening & Constraints
1. **Graded State Immutability**: If `existing['status'] === 'graded'`, the controller rejects resubmissions with an error message preventing tampering with graded work.
2. **Canonical Upload Destination**: Submissions are stored under `storage/uploads/lms/submissions/` with automatic directory creation (`0775`).
3. **File Validation**:
   - File size cap: 25 MB max.
   - Blacklist validation: Prohibits executable extensions (`php`, `phtml`, `exe`, `sh`, `bat`, `cmd`, `js`, `vbs`, `jar`).
   - Obfuscated storage name: `sub_{assignmentId}_{studentId}_{randomHex}.{ext}`.
   - Real MIME type inspection via PHP `finfo_file(FILEINFO_MIME_TYPE)`.
4. **CSRF Protection**: Form in `assignments/show.php` includes hidden CSRF token verified on POST.
5. **Cross-Course Guard**: Validates that `assignment['lms_course_id'] === $lmsCourseId` before processing uploads.

---

## 7. QUIZZES & ATTEMPT ENGINE

- **Controllers**:
  - Quiz List & Details: [`app/Controllers/Lms/StudentQuizController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentQuizController.php) (`index()`, `show()`)
  - Attempt Engine: [`app/Controllers/Lms/StudentQuizController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentQuizController.php) (`start()`, `attempt()`, `submit()`, `result()`)
- **Service**: [`app/Services/LmsQuizService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsQuizService.php)
- **Views**:
  - Detail: [`app/Views/lms/student/quizzes/show.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/quizzes/show.php)
  - In-Progress Exam: [`app/Views/lms/student/quizzes/attempt.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/quizzes/attempt.php)
  - Results / Review: [`app/Views/lms/student/quizzes/result.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/quizzes/result.php)

### 7.1 In-Progress Resume Fix
- **Problem**: When a quiz had `max_attempts = 1` and a student had already initiated an `in_progress` attempt, calling `startAttempt()` checked `$attemptCount >= $quiz['max_attempts']` before inspecting if an `in_progress` attempt existed. This prematurely blocked students from resuming an unfinished attempt.
- **Solution**: Re-ordered checks in [`LmsQuizService::startAttempt()`](file:///c:/xampp/htdocs/sia/app/Services/LmsQuizService.php):
  ```php
  // Check if there is an in-progress attempt to resume first
  foreach ($attempts as $att) {
      if ($att['status'] === 'in_progress') {
          return (int)$att['id']; // Resume existing
      }
  }

  $attemptCount = count($attempts);
  if ($quiz['max_attempts'] !== null && $attemptCount >= $quiz['max_attempts']) {
      return null; // Max attempts reached
  }
  ```

### 7.2 Strict IDOR & Attempt Isolation
1. **Course Isolation**: All quiz endpoints verify `$quiz['lms_course_id'] === (int)$lmsCourseId`. Manipulating URL course context to access another course's quiz fails immediately.
2. **Attempt Ownership**: In `attempt()`, `submit()`, and `result()`, the attempt is verified against session user:
   ```php
   if ((int)$attempt['student_id'] !== $userId) {
       // Access Denied
   }
   ```
3. **Publication Enforcement**: Students cannot view or attempt draft (`status != 'published'`) quizzes.
4. **Time & Expiration Enforcements**: Quizzes outside their `start_date` or `end_date` windows reject attempt creations.

---

## 8. STUDENT GRADEBOOK PERFORMANCE REFACTOR

- **Service**: [`app/Services/LmsGradebookService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php)
- **Controller**: [`app/Controllers/Lms/StudentGradebookController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentGradebookController.php)
- **View**: [`app/Views/lms/student/gradebook/index.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/gradebook/index.php)

### 8.1 Performance Bottleneck Eliminated ($O(N) \to O(1)$)
- **Audit Finding**: Previously, when a student visited their course gradebook, the system called `getStudentGradebook()`, which internally called `getClassGradebook($lmsCourseId)`. This method loaded every enrolled student in the entire class, fetched all assignments and quizzes, computed class-wide distributions, and searched the array for the single requesting student. In classes with 40–50 students, this resulted in massive query overhead and memory waste.
- **Refactoring**: Implemented dedicated personal gradebook method:
  ```php
  public function getStudentPersonalGradebook(int $lmsCourseId, int $studentId): array
  ```
  - **Query Count**: Executes only 3 targeted queries:
    1. Fetch course assessments (assignments and quizzes).
    2. Fetch ONLY this student's assignment submissions.
    3. Fetch ONLY this student's quiz attempts.
  - **Privacy Guarantee**: No data from any other student in the class is ever queried or brought into server memory.

---

## 9. COURSE ANNOUNCEMENTS & BULLETINS

- **Controller**: [`app/Controllers/Lms/StudentAnnouncementController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAnnouncementController.php)
- **View**: [`app/Views/lms/student/announcements/index.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/announcements/index.php)
- **Audited Capabilities**:
  - Bulletins are bound strictly to authorized course shells.
  - Sorted chronologically descending (`pinned DESC, created_at DESC`).
  - Cross-course URL tampering is rejected by `isStudentAuthorizedForCourse()`.

---

## 10. ATTENDANCE

- **Controller**: [`app/Controllers/Lms/StudentAttendanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/StudentAttendanceController.php)
- **View**: [`app/Views/lms/student/attendance/index.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student/attendance/index.php)
- **Audit & Hardening**:
  - Student attendance is read-only.
  - Course authorization is strictly validated before querying attendance.
  - Lowercase normalization (`strtolower($h['status'])`) implemented on status counters (`present`, `late`, `absent`, `excused`) ensuring accurate calculation regardless of database case formatting.
  - Viewing other students' attendance is impossible (queries filter exclusively on `WHERE student_id = :sid`).

---

## 11. SECURITY & IDOR DEFENSE MATRIX

| Resource | Attack Vector Tested | Defense Mechanism | Result |
| :--- | :--- | :--- | :--- |
| **Course** | Student requests another section's course | `isStudentAuthorizedForCourse()` SQL verification | **403 Forbidden** |
| **Course** | Student requests SHS course when in College | Level & enrollment table verification | **403 Forbidden** |
| **Course** | Non-existent Course ID | Checked in DB; returns false / 404 | **Safe Error** |
| **Material** | Direct download link manipulation | `DownloadController` checks `isStudentAuthorizedForCourse()` | **403 Forbidden** |
| **Material** | Path traversal (`../../etc/passwd`) | `basename()` sanitization | **Sanitized / Rejected** |
| **Assignment** | Submitting to another course's assignment | Validates `assignment['lms_course_id'] === $courseId` | **Rejected** |
| **Assignment** | Tampering with graded assignment | Server checks `status === 'graded'` | **Resubmission Blocked** |
| **Quiz** | Accessing quiz belonging to another course | Controller validates quiz course binding | **404 / 403 Denied** |
| **Quiz Attempt**| Submitting answers for another student's attempt | Verifies `attempt['student_id'] === $sessionUserId` | **Access Denied** |
| **Quiz Attempt**| Accessing another student's attempt results | Verifies `attempt['student_id'] === $sessionUserId` | **Access Denied** |
| **Gradebook** | Requesting class-wide grade endpoints | Controller only invokes `getStudentPersonalGradebook()` | **Isolated to Student** |
| **Faculty Role**| Student invoking faculty actions | `RoleMiddleware` checks `$_SESSION['role'] === 'faculty'` | **403 / Redirect** |

---

## 12. AUTOMATED VERIFICATION RESULTS

An automated verification test script was created to comprehensively validate the student LMS:
- **Test File**: [`scripts/test_phase3_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase3_verification.php)

```text
====================================================================
           STARTING PHASE 3 AUTOMATED VERIFICATION SUITE            
====================================================================

[PASS] Test 1: Enrolled student can access LMS courses (Student UID 11 resolved 4 active courses including Course 1)
[PASS] Test 2: Approved-but-not-enrolled applicant cannot access LMS (Applicant without enrolled status is rejected from LMS authorization)
[PASS] Test 3: Dropped subject is removed from active LMS access (Course 1 removed from courses, auth false, dropped_at timestamped)
[PASS] Test 4: Withdrawn subject is removed from active LMS access (Course 1 removed from courses, status 'withdrawn', records preserved)
[PASS] Test 5: Section transfer updates course access (Transferred to BSIT 1-B: Course 1 revoked, Course 11 active)
[PASS] Test 6: Student cannot access un-enrolled course from another student/level (College student rejected from SHS Course 4)
[PASS] Test 7: Section A student cannot access Section B course (Section 1 student denied access to Section 3 course shell (Course 11))
[PASS] Test 8: Student cannot access faculty functionality (Student UID 11 rejected by isFacultyAuthorizedForCourse)
[PASS] Test 9: Authorized student can access course materials (Course 1 modules resolved with materials; student verified)
[PASS] Test 10: Unauthorized student cannot download material (Non-enrolled student rejected from downloading course material)
[PASS] Test 11: Student can view own course assignments (Retrieved 1 published assignments for Course 1)
[PASS] Test 12: Student can submit and resubmit assignment without duplicate key errors (Submission updated via ON DUPLICATE KEY UPDATE: status=RESUBMITTED)
[PASS] Test 13: Student cannot submit to another course's assignment (Assignment course mismatch verified; cross-course submission blocked)
[PASS] Test 14: Student can access authorized quiz (Found 1 published quizzes for Course 1)
[PASS] Test 15: Student cannot access another course's quiz (Course 1 quiz rejected when requested under Course 11 context)
[PASS] Test 16: Student cannot modify or submit another student's attempt (Attempt 2 strictly bound to student UID 11)
[PASS] Test 17: Student sees only own grades in personal gradebook (Personal gradebook contains only UID 11; no full class roster exposed)
[PASS] Test 18: Student cannot access another student's grades (Separate student request returns isolated, independent grade profile)
[PASS] Test 19: Student gradebook uses student-specific retrieval (O(1) student complexity) (Gradebook loads directly for requested student without class grid aggregation)
[PASS] Test 20: Direct ID manipulation across resources is rejected server-side (Non-existent course, quiz, assignment, and submission IDs safely rejected)

====================================================================
SUCCESS: ALL 20 PHASE 3 VERIFICATION TESTS PASSED!
====================================================================
```

### Regression Verification:
- **Phase 1 Verification**: 14 / 14 Scenarios Passed (`php scripts/test_phase1_verification.php`).
- **Phase 2 Verification**: 8 / 8 Scenarios Passed (`php scripts/test_phase2_verification.php`).
