# TTU LMS PHASE 4 FACULTY LMS COMPLETION SPECIFICATION

> **Subsystem**: Faculty Learning Management System (LMS)  
> **Phase**: Phase 4 — Faculty LMS Completion  
> **Status**: Completed & Verified (24/24 Phase 4 Tests Passed; Zero Regressions in Phases 1, 2, and 3: 66/66 Total Passing)  
> **Implementation Date**: September 30, 2026  
> **Authoritative Root**: `c:\xampp\htdocs\sia`  

---

## 1. ARCHITECTURAL OVERVIEW & DATA FLOW

Phase 4 completes, stabilizes, and hardens the Faculty LMS experience. The Faculty LMS allows authorized faculty members to manage instructional content, materials, assignments, quizzes, gradebooks, rosters, announcements, and attendance for courses officially assigned to them through the Enrollment and Scheduling system.

### Faculty Access & Authorization Pipeline:
```text
┌────────────────────────────────────────────────────────┐
│                   Faculty Authentication               │
│                     Role: 'faculty'                    │
│            `users.role` = 'faculty' & `is_active` = 1   │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│               Faculty Role Verification                │
│              `RoleMiddleware:faculty`                  │
│       Non-faculty roles redirected to own portal        │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│               Assigned LMS Course Shells               │
│          `lms_courses.faculty_user_id` = :userId       │
│          AND `lms_courses.status` = 'active'           │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│            Server-Side Course Authorization            │
│       `LmsService::isFacultyAuthorizedForCourse()`     │
│   Guards every controller action (view, edit, delete)  │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│               Faculty Course Management                │
│    - Course Overview & Instructional Modules           │
│    - Canonical Learning Materials (Upload / Delete)    │
│    - Assignments & Submission Grading                  │
│    - Timed Quizzes, Questions & Attempt Results        │
│    - High-Performance Bulk-Prefetched Gradebook        │
│    - Real-Time Student Roster (Regular & Irregular)    │
│    - Course Announcements & Attendance Logging         │
└────────────────────────────────────────────────────────┘
```

---

## 2. FACULTY ACCESS CONTROL & RBAC

### 2.1 Route Guarding & Middleware Pipeline
All faculty routes are grouped under:
```php
$router->group([
    'middleware' => [
        'App\Middleware\SessionSecurityMiddleware',
        'App\Middleware\CsrfMiddleware',
        'App\Middleware\AuthMiddleware',
        'App\Middleware\RoleMiddleware:faculty'
    ]
], function (Router $router) { ... });
```

- **Unauthenticated user**: Redirected immediately to `/sia/auth/login.php`.
- **Student accessing faculty routes**: [`RoleMiddleware`](file:///c:/xampp/htdocs/sia/app/Middleware/RoleMiddleware.php) intercepts and redirects to `/sia/lms/student/dashboard.php`.
- **Cashier / Admissions / Scheduler**: Intercepted and redirected to their departmental dashboards.
- **CSRF Defense**: All POST actions (module create/update/delete, material upload/delete, assignment create/update/delete/grade, quiz create/update/delete, announcement create/update/delete, attendance store/update) enforce valid CSRF tokens via `CsrfMiddleware`. All faculty views include `<?= getCsrfInput() ?>`.

### 2.2 Server-Side Course Authorization
Authorization is strictly checked on the backend, never relying on UI visibility:
```php
public function isFacultyAuthorizedForCourse(int $userId, int $lmsCourseId): bool
{
    $stmt = $this->pdo->prepare("
        SELECT 1 FROM lms_courses 
        WHERE id = :lcid AND faculty_user_id = :uid AND status = 'active'
    ");
    $stmt->execute(['lcid' => $lmsCourseId, 'uid' => $userId]);
    if ((bool)$stmt->fetchColumn()) {
        return true;
    }

    // Grant oversight access to superadmin, admin, or users with LMS management permissions
    $userStmt = $this->pdo->prepare("SELECT role, permissions FROM users WHERE id = :uid AND is_active = 1");
    $userStmt->execute(['uid' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (in_array($user['role'], ['superadmin', 'admin'], true)) {
            return true;
        }
        if (!empty($user['permissions'])) {
            $perms = json_decode($user['permissions'], true);
            if (is_array($perms) && (in_array('*', $perms, true) || in_array('lms.courses.manage', $perms, true) || in_array('lms.courses.view_all', $perms, true))) {
                return true;
            }
        }
    }

    return false;
}
```

---

## 3. FACULTY COURSE DASHBOARD & TEACHING LOAD

- **Assigned Courses Query**: [`LmsService::getFacultyCourses(int $facultyUserId)`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) fetches only courses where `lc.faculty_user_id = :fid` and `lc.status = 'active'`.
- **TBA & Unassigned Shells**: Courses with `faculty_user_id = NULL` are completely excluded from arbitrary faculty dashboards. They can neither be viewed nor claimed by arbitrary faculty members.
- **Teaching Load Statistics**:
  - Total active courses count.
  - Total officially enrolled students count (derived across sections and irregular enrollments).
  - Pending grading queue count (`lms_submissions.status = 'SUBMITTED'`).
  - Recent student submissions and recent announcements.

---

## 4. IRREGULAR STUDENT HANDLING & ROSTER TRUTH

### 4.1 Root Cause of Previous Defect
In legacy queries, student counts were often derived via `applications.section_id = lc.academic_section_id`. However, irregular students frequently have `applications.section_id = NULL` because they do not belong to a single cohort block; instead, their subject enrollments are tracked individually in `college_enrollments` or `shs_enrollments`.

### 4.2 Canonical Roster Implementation
[`LmsService::getCourseRoster(int $lmsCourseId)`](file:///c:/xampp/htdocs/sia/app/Services/LmsService.php) establishes academic enrollment as the sole source of truth:
```sql
SELECT 
    u.id, 
    u.student_number, 
    u.first_name, 
    u.last_name, 
    u.email,
    a.id as application_id,
    a.status as application_status,
    a.student_type as enrollment_type,
    a.student_type,
    ce.status as enrollment_status,
    ce.college_section_id,
    cs.section_code,
    ce.created_at as enrolled_at
FROM college_enrollments ce
JOIN applications a ON ce.application_id = a.id
JOIN users u ON a.user_id = u.id
LEFT JOIN college_sections cs ON ce.college_section_id = cs.id
WHERE ce.college_section_id = :sec 
  AND ce.subject_id = :sub
  AND ce.status = 'enrolled'
  AND a.status = 'enrolled'
ORDER BY u.last_name ASC, u.first_name ASC
```
- **Regular and Irregular Students**: Accurately returned and counted based on actual subject enrollment.
- **Lifecycle Drop / Withdrawal**: Any student whose `ce.status` is `'dropped'` or `'withdrawn'` is immediately omitted from active rosters and gradebooks, preserving historical records.

---

## 5. COURSE MODULE & MATERIAL LIFECYCLE

### 5.1 Module Management
- Faculty can create, update title/order, and delete instructional modules.
- **Ownership Verification**: Before editing or deleting a module, [`FacultyController`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyController.php) verifies both course authorization and that the module actually belongs to the target course:
```php
$mod = $lmsService->getModule($moduleId);
if (!$mod || (int)$mod['lms_course_id'] !== $courseId) {
    $response->setStatusCode(404);
    echo "404 Not Found - Module does not belong to this course.";
    exit;
}
```

### 5.2 Material Management & Canonical Storage
- **Storage Location**: Canonical path `storage/uploads/lms/materials/`.
- **Validation**: Uploads are validated against permitted MIME types, dangerous extensions (e.g. `.php`, `.phtml`, `.exe`), and size limits (25MB).
- **Physical Cleanups**:
  - Deleting a single material unlinks the physical file from disk and deletes the database record.
  - Deleting an entire module queries all child materials and deletes all associated physical files from disk before deleting the database record.

---

## 6. ASSIGNMENTS & SUBMISSION MANAGEMENT

### 6.1 Assignment Operations
- **Creation & Update**: Title, description/instructions, due date, max score, draft/published status.
- **Cross-Course Guarding**: [`FacultyAssignmentController`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/FacultyAssignmentController.php) verifies that the assignment ID belongs to the course ID in the URL.
- **Physical File Cleanups on Deletion**: When an assignment is deleted, all student submission files stored on disk in `storage/uploads/lms/submissions/` are automatically unlinked before the assignment record is removed.

### 6.2 Submission Grading
- Instructors view submissions submitted by students in their course.
- **Strict Server-Side Validation**:
  - Verifies instructor is authorized for the course.
  - Verifies assignment belongs to the course.
  - Verifies submission belongs to the assignment.
  - Records numeric grade, feedback comments, sets status to `'GRADED'`, and tags `graded_by = :userId`.

---

## 7. HIGH-PERFORMANCE GRADEBOOK ARCHITECTURE

### 7.1 Elimination of N+1 Query Bottleneck
In the legacy implementation, [`LmsGradebookService::getCourseGradebook()`](file:///c:/xampp/htdocs/sia/app/Services/LmsGradebookService.php) executed two database queries *for every student in the course* inside nested loops:
$$\text{Queries} = 4 + (\text{Students} \times \text{Assignments}) + (\text{Students} \times \text{Quizzes})$$
For a section of 40 students with 4 assignments and 4 quizzes, this triggered over **324 SQL queries** per page load!

### 7.2 Bulk Pre-Fetching Optimization
The gradebook was refactored to pre-fetch all submissions and all quiz attempts in bulk using single `IN (...)` queries:
```php
// Pre-fetch all submissions for course assignments in one query
if (!empty($assignmentIds)) {
    $inAssign = implode(',', array_map('intval', $assignmentIds));
    $subStmt = $this->pdo->query("SELECT user_id, assignment_id, grade, status FROM lms_submissions WHERE assignment_id IN ($inAssign)");
    while ($row = $subStmt->fetch(PDO::FETCH_ASSOC)) {
        $submissionsByStudentAndAssign[(int)$row['user_id']][(int)$row['assignment_id']] = $row;
    }
}

// Pre-fetch all quiz attempts for course quizzes in one query
if (!empty($quizIds)) {
    $inQuiz = implode(',', array_map('intval', $quizIds));
    $attStmt = $this->pdo->query("
        SELECT user_id, lms_quiz_id, score, is_passed, status 
        FROM lms_quiz_attempts 
        WHERE lms_quiz_id IN ($inQuiz) AND status = 'completed'
        ORDER BY score DESC
    ");
    while ($row = $attStmt->fetch(PDO::FETCH_ASSOC)) {
        $uid = (int)$row['user_id'];
        $qid = (int)$row['lms_quiz_id'];
        if (!isset($attemptsByStudentAndQuiz[$uid][$qid])) {
            $attemptsByStudentAndQuiz[$uid][$qid] = $row;
        }
    }
}
```
**Result**: Total database queries per gradebook view reduced to **4 queries total** ($O(1)$ query complexity regardless of class size).

---

## 8. QUIZZES & ASSESSMENT RESULTS

- **Quiz Management**: Faculty can create quizzes (time limit, max attempts, passing score, draft/published), add questions (multiple choice, true/false, identification, essay), and configure answer choices and points.
- **Deletions**: Supported at question level and quiz level with database cascade and ownership validation.
- **Results & Attempts**: Faculty can inspect all student attempts and scores for quizzes belonging to their course. Attempts from unauthorized courses or students cannot be accessed.

---

## 9. COURSE CALENDAR ROUTING REPAIR

- **Previous Bug**: In [`LmsCalendarService`](file:///c:/xampp/htdocs/sia/app/Services/LmsCalendarService.php), calendar event links for both faculty and students were hardcoded to student URLs (`/sia/lms/student/course/{id}/assignments/{aid}`). When faculty clicked an assignment on their calendar, they were blocked by `RoleMiddleware:student`.
- **Fix Applied**: Added contextual URL generation based on `$isFaculty`:
  - Faculty assignments route to: `/sia/lms/faculty/course/{cid}/assignments/{id}/submissions`
  - Faculty quizzes route to: `/sia/lms/faculty/course/{cid}/quizzes/{id}/results`
  - Student events continue routing to student viewing endpoints.

---

## 10. FACULTY REASSIGNMENT & TBA UNASSIGNED SHELLS

### 10.1 Faculty Reassignment Lifecycle
When the scheduling administrator reassigns a section's subject from Faculty A to Faculty B:
1. `lms_courses.faculty_user_id` is updated to Faculty B's ID.
2. Faculty A immediately loses access (server-side authorization returns false).
3. Faculty B immediately gains access and sees the course on their dashboard.
4. All course content (modules, materials, assignments, submissions, quizzes, grades, roster) remains 100% intact.
5. No duplicate course shells are created.

### 10.2 TBA Unassigned Course Lifecycle
When a course has no assigned instructor (`faculty_user_id = NULL`):
1. The course shell remains valid and active.
2. On student course views, the instructor displays cleanly as `'TBA'`.
3. Server-side authorization rejects any arbitrary faculty attempting to access the course until officially assigned.

---

## 11. SECURITY & IDOR DEFENSE MATRIX

| Target Resource | Tested Manipulation | Server-Side Defense | Expected & Verified Outcome |
| :--- | :--- | :--- | :--- |
| **Course ID** | Faculty A accesses Course B (`?id=2`) | `isFacultyAuthorizedForCourse()` | HTTP 403 Forbidden |
| **Course ID** | Student accesses faculty course (`?id=1`) | `RoleMiddleware:faculty` | Redirected to student dashboard |
| **Course ID** | Non-existent course (`?id=999999`) | Null check in `getCourseDetails()` | HTTP 404 Not Found |
| **Module ID** | Modifying Module B under Course A | Checks `mod.lms_course_id === $courseId` | HTTP 404 / 403 Rejected |
| **Material ID** | Deleting material of another faculty | Course authorization check on parent module | HTTP 403 Forbidden |
| **Assignment ID** | Faculty B modifying Course A assignment | Course authorization check on assignment | HTTP 403 Forbidden |
| **Submission ID** | Grading submission of Course B in Course A | Verifies `submission.assignment_id` belongs to `course_id` | HTTP 404 / 403 Forbidden |
| **Quiz ID** | Deleting quiz belonging to another course | Course authorization check on quiz | HTTP 403 Forbidden |
| **Question ID** | Deleting question from another quiz | Verifies `question.lms_quiz_id === $quizId` | HTTP 403 Forbidden |
| **Student Roster** | Cross-section student data leakage | Filtered by `(academic_section_id, subject_id)` | Zero cross-section leakage |

---

## 12. COMPREHENSIVE AUTOMATED VERIFICATION RESULTS

The 24-point regression suite in [`scripts/test_phase4_verification.php`](file:///c:/xampp/htdocs/sia/scripts/test_phase4_verification.php) passed completely:

```text
====================================================================
           STARTING PHASE 4 AUTOMATED VERIFICATION SUITE            
====================================================================

[PASS] Test 1: Faculty A sees assigned courses (Faculty 9 retrieved 6 courses, including Course 1)
[PASS] Test 2: Faculty A cannot see Faculty B's courses (Course 2 excluded from Faculty A list and isFacultyAuthorizedForCourse returned false)
[PASS] Test 3: Student cannot access faculty routes (Student UID 11 rejected by isFacultyAuthorizedForCourse and RoleMiddleware:faculty)
[PASS] Test 4: Unrelated authenticated users cannot access faculty routes (Cashier UID 4 denied course management authority)
[PASS] Test 5: Faculty can manage own course (Faculty 9 verified for Course 1 (CC101 - BSIT 1-A))
[PASS] Test 6: Faculty cannot modify another faculty's course (Faculty B (UID 8) rejected when attempting to access Course A (ID 1))
[PASS] Test 7: Module ownership is enforced (Module created, verified course binding, and updated under authorization)
[PASS] Test 8: Material ownership is enforced (Material uploaded, cross-faculty delete prevented, authorized delete verified)
[PASS] Test 9: Assignment ownership is enforced (Assignment lifecycle and cross-course modification protections confirmed)
[PASS] Test 10: Quiz ownership is enforced (Quiz and question lifecycle strictly bound to authorized course)
[PASS] Test 11: Regular students appear correctly (Found 5 regular students in Course 1 roster)
[PASS] Test 12: Irregular students appear correctly (Found 3 irregular students including UID 23 in Course 1 roster (Total: 13))
[PASS] Test 13: Dropped students are handled correctly (Dropped student UID 11 safely excluded from active roster and gradebook)
[PASS] Test 14: Withdrawn students are handled correctly (Withdrawn student UID 11 safely excluded from active roster and gradebook)
[PASS] Test 15: Faculty A -> Faculty B reassignment works correctly (Course 1 faculty_user_id successfully updated to 8)
[PASS] Test 16: Faculty A loses access (Previous instructor (UID 9) denied access to Course 1)
[PASS] Test 17: Faculty B gains access (New instructor (UID 8) granted access to Course 1)
[PASS] Test 18: Existing course content remains intact (Course content preserved across reassignment (modules, assignments, roster))
[PASS] Test 19: Unassigned course remains valid (Course 1 loads cleanly as TBA shell)
[PASS] Test 20: Arbitrary faculty cannot claim unassigned course (Both Faculty A and Faculty B denied access to unassigned TBA course)
[PASS] Test 21: ID manipulation is rejected across resources (Invalid and non-existent IDs safely return null without SQL or system faults)
[PASS] Test 22: Cross-course submission/grade access is rejected (Submissions and grading strictly isolated to assignment's parent course)
[PASS] Test 23: Cross-course quiz access is rejected (Course 1 quiz rejected when evaluated against Faculty B authority)
[PASS] Test 24: Cross-course student data access is rejected (Section 1 roster (13) and Section 3 roster (5) have 0 unexpected student overlap)

====================================================================
SUCCESS: ALL 24 PHASE 4 VERIFICATION TESTS PASSED! (24/24)
====================================================================
```

### Cumulative Verification Across All Phases:
- **Phase 1 Verification**: 14 / 14 Passed
- **Phase 2 Verification**: 8 / 8 Passed
- **Phase 3 Verification**: 20 / 20 Passed
- **Phase 4 Verification**: 24 / 24 Passed
- **Total Passing Tests**: **66 / 66 (100% Pass Rate, Zero Regressions)**

---

## 13. KNOWN LIMITATIONS & PHASE 5 READINESS

### 13.1 Known Limitations (Kept within Intentional Scope)
- **Live Video Conferencing**: Not part of Phase 4; faculty communicate asynchronously via course announcements and module materials.
- **Rubrics-Based Grading**: Currently supports numeric points and descriptive qualitative instructor feedback. Advanced rubric matrices are scheduled for administrative governance.

### 13.2 Phase 5 Readiness
The system is fully stabilized and prepared for **Phase 5 — LMS Administration & Governance**:
- Course shells are provisioned and synchronized with Enrollment and Scheduling.
- Student LMS is fully isolated and operational.
- Faculty LMS is fully hardened, secured against IDOR, and optimized for performance.
