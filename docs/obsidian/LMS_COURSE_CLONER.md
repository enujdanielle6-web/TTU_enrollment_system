# LMS Admin Course Content / Syllabus Template Cloner

## Executive Summary

The **LMS Admin Course Content / Syllabus Template Cloner** is an administrative governance tool that empowers academic administrators and department coordinators to replicate proven instructional structures (syllabus chapters, lecture materials, assignment prompts, and quizzes) across academic terms or sections without manual re-entry.

```
       [SOURCE COURSE]                                  [TARGET COURSE]
  (Archived or Template Shell)                       (Active Clean Shell)
   ├── lms_modules (Modules)        ───────────>      ├── lms_modules (New IDs)
   ├── lms_materials (Files)        ───────────>      ├── lms_materials (New IDs)
   ├── lms_assignments (Prompts)   ───────────>      ├── lms_assignments (due_date = NULL)
   └── lms_quizzes (Quiz Structure) ───────────>      └── lms_quizzes (start/end = NULL)
         ├── lms_questions          ───────────>            ├── lms_questions (New IDs)
         └── lms_question_choices   ───────────>            └── lms_question_choices (New IDs)

   ══════════════════════════════════════════════════════════════════════════════
   STRICT ISOLATION BOUNDARY: ZERO STUDENT ARTIFACTS ARE EVER COPIED
   ══════════════════════════════════════════════════════════════════════════════
   [X] lms_submissions             ──X  (NEVER COPIED)
   [X] lms_quiz_attempts           ──X  (NEVER COPIED)
   [X] lms_quiz_answers            ──X  (NEVER COPIED)
   [X] lms_attendance_sessions     ──X  (NEVER COPIED)
   [X] lms_attendance_records      ──X  (NEVER COPIED)
   [X] college_enrollments         ──X  (NEVER COPIED)
   [X] student grades/transcripts  ──X  (NEVER COPIED)
```

---

## 1. Architectural Domain Invariant

> **ENROLLMENT OWNS ACADEMIC TRUTH. LMS OWNS THE LEARNING EXPERIENCE.**

- **Enrollment / Registrar Domain**: Authoritative source of academic truth. Manages student identity (`users`), matriculation applications (`applications`), section capacities (`college_sections`), curricula (`curriculum`), official timetables (`college_section_subjects`), and enrolled rosters (`college_enrollments`).
- **LMS Domain**: Manages the learning delivery experience. Contains course shells (`lms_courses`), syllabus chapters (`lms_modules`), reference files (`lms_materials`), coursework prompts (`lms_assignments`), and practice assessments (`lms_quizzes`).
- **Cloner Boundary**: The cloner belongs exclusively to the **LMS Domain**. It interacts only with instructional structures and never touches student enrollment, registrar rosters, or official grades.

---

## 2. Content Scope & Lifecycle Rules

### What Gets Copied
1. **Modules (`lms_modules`)**:
   - `title`: Chapter/week title.
   - `description`: Instructional overview and learning outcomes.
   - `display_order`: Preserved or shifted based on target offset.
   - `status`: Published or draft status.
2. **Materials (`lms_materials`)**:
   - `file_name`: Reference document or lecture slide name.
   - `file_path`: Relative storage path.
   - `mime_type` and `file_size`: Document metadata.
   - Re-linked to the newly created target module.
3. **Assignments (`lms_assignments`)**:
   - `title`: Assignment prompt title.
   - `description`: Instructions and rubrics.
   - `max_score`: Max points possible.
   - `status`: Draft or published status.
   - `file_path`: Attached reference prompt file if present.
   - Re-linked to the newly created target module (or left standalone).
   - **`due_date` is explicitly reset to `NULL`** to prevent overdue penalties in the new semester before the instructor sets the academic calendar.
4. **Quizzes (`lms_quizzes`)**:
   - `title`: Quiz title.
   - `description`: Assessment instructions.
   - `time_limit`: Duration in minutes.
   - `max_attempts`: Allowed attempt count.
   - `passing_score`: Minimum passing threshold.
   - `status`: Draft or published status.
   - **`start_date` and `end_date` are explicitly reset to `NULL`**.
5. **Questions (`lms_questions`)**:
   - `question_text`: Question prompt.
   - `question_type`: Multiple choice or true/false.
   - `points`: Weight of question.
   - `display_order`: Sequence inside the quiz.
   - Re-linked to the newly created target quiz.
6. **Question Choices (`lms_question_choices`)**:
   - `choice_text`: Option text.
   - `is_correct`: Correct answer flag.
   - `display_order`: Option sequence.
   - Re-linked to the newly created target question.

### What Is NEVER Copied
The following entities are strictly excluded by query design and foreign-key isolation:
- **Student Enrollments**: `college_enrollments`, `shs_enrollments`, and `applications` remain 100% untouched.
- **Student Submissions**: Zero records from `lms_submissions` are transferred.
- **Student Quiz Attempts & Answers**: Zero records from `lms_quiz_attempts` and `lms_quiz_answers` are transferred.
- **Attendance Records**: Zero records from `lms_attendance_sessions` or `lms_attendance_records` are transferred.
- **User Accounts & Roles**: No faculty or student records are modified.
- **Registrar Records**: Subject catalogs, curriculum records, and transcript data are completely untouched.

---

## 3. Authorization & User Access

- **Authorized Roles**:
  - `superadmin`: Full access.
  - `admin`: Full access.
  - Users with explicit LMS administrative permissions: `lms.manage`, `lms.admin`, `lms.courses.manage`.
- **Denied Roles**:
  - `scheduler`: Strictly denied (HTTP 403) despite having `sections.manage`.
  - `cashier`, `admissions`, `clinic`, `scholarship`: Strictly denied (HTTP 403).
  - `faculty`: Denied from administrative cloner (faculty manage their own shells).
  - `student`: Strictly denied (HTTP 403).
  - Unauthenticated requests: Terminated immediately with HTTP 403.

---

## 4. Operation Modes & Collision Prevention

To prevent accidental duplication or data loss, the cloner supports two distinct modes:

| Operation Mode | Behavior | Safety Guarantee | Use Case |
| :--- | :--- | :--- | :--- |
| **`empty_only`** *(Default & Recommended)* | Checks target content before executing. If target contains $\ge 1$ module, assignment, or quiz, the operation **immediately aborts** with an error. | **100% collision-free**. No duplicate chapters or assessments can be created. | Standard semester kickoff when setting up newly generated empty course shells. |
| **`append`** *(Explicit Admin Choice)* | Appends cloned modules, assignments, and quizzes. Discovers target's maximum `display_order` and shifts cloned modules (`display_order + max_order + 1`). | Preserves existing content; inserts new items sequentially after existing modules. | Combining multiple modular units or adding supplemental midterm/final modules. |

> **Destructive Overwrite**: TTU LMS deliberately **does NOT** implement a destructive overwrite mode. Target content is never wiped out, eliminating the risk of catastrophic administrative deletion.

---

## 5. Transaction Strategy & Rollback Safety

All mutations execute within a single atomic PDO transaction:
```php
$this->pdo->beginTransaction();
try {
    // 1. Insert Target Modules & Map IDs ($moduleIdMap)
    // 2. Insert Target Materials linked to new Module IDs
    // 3. Insert Target Assignments linked to new Module IDs (due_date = NULL)
    // 4. Insert Target Quizzes & Map IDs ($quizIdMap, dates = NULL)
    // 5. Insert Target Questions & Map IDs ($questionIdMap)
    // 6. Insert Target Question Choices linked to new Question IDs
    $this->pdo->commit();
} catch (\Throwable $e) {
    if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
    }
    // Log failure to activity_logs
    throw $e;
}
```

- **All-or-Nothing Guarantee**: If any failure occurs (e.g. database timeout, constraint violation, foreign key mismatch), the transaction rolls back completely.
- **Zero Half-Cloned State**: Target course is never left with partial modules or missing materials.
- **Source Immutability**: All source queries are read-only (`SELECT`). The source course remains 100% untouched.

---

## 6. Audit Trail Integration

The Cloner integrates with the existing TTU `activity_logs` table via `logActivity()`:
- **Success Action**:
  - `icon`: `'bi-copy'`
  - `title`: `'LMS Course Content Cloned'`
  - `description`: Record count summary (modules, materials, assignments, quizzes, questions, choices).
  - `affected_record`: `'lms_courses:{target_id}'`
  - `reason`: `'Course Syllabus / Template Cloner'`
- **Failure Action**:
  - `icon`: `'bi-exclamation-triangle'`
  - `title`: `'LMS Course Clone Failed'`
  - `description`: Error message and origin source/target IDs.
  - `affected_record`: `'lms_courses:{target_id}'`

All clone events automatically appear in the LMS Audit Logs view (`/sia/admin/lms/audit_logs`).

---

## 7. Files Changed & Added

| File Path | Type | Modification |
| :--- | :--- | :--- |
| `app/Services/LmsAdminService.php` | Modified | Added `getClonableCourses()`, `getClonePreview()`, and `cloneCourseContent()` with atomic PDO transaction, ID remapping, date reset, and collision protection. |
| `app/Controllers/Admin/LmsAdminController.php` | Modified | Added `templateCloner()` (GET) and `processCloneContent()` (POST) with CSRF validation and RBAC enforcement. |
| `app/Routes/web.php` | Modified | Registered `/admin/lms/cloner` and `/admin/lms/cloner/process`. |
| `app/Views/admin/lms/cloner/index.php` | Created | Modern, responsive Bootstrap 5 interface featuring source/target selector, live preview, warnings, mode options, and confirmation modal. |
| `app/Views/admin/lms/dashboard.php` | Modified | Added "Course Templates & Content Cloner" quick action card in LMS Admin dashboard. |
| `app/Views/admin/lms/courses/detail.php` | Modified | Added "Clone Content" shortcut button in header dossier strip. |
| `scripts/test_lms_admin_cloner.php` | Created | Comprehensive automated verification suite testing 10 distinct failure/success scenarios (59/59 assertions passed). |

---

## 8. Verification & Test Matrix

Executed via `scripts/test_lms_admin_cloner.php`:

| Test Category | Scenario | Expected Behavior | Result |
| :--- | :--- | :--- | :--- |
| **RBAC** | Superadmin / Admin access | Authorized | **PASS** |
| **RBAC** | Scheduler / Student / Guest access | HTTP 403 Access Denied | **PASS** |
| **Input Validation** | Non-existent source or target ID | Throws Exception | **PASS** |
| **Input Validation** | Identical source and target IDs | Throws Exception | **PASS** |
| **Input Validation** | Source course has 0 instructional items | Throws Exception | **PASS** |
| **Pre-Flight Preview** | Breakdown of modules, files, assignments, quizzes | Accurately aggregates counts | **PASS** |
| **Pre-Flight Preview** | Cross-subject cloning attempt | Displays Amber warning | **PASS** |
| **Empty Target Clone** | Course 1 (8 mods, 3 files, 1 assg, 1 quiz, 3 questions, 8 choices) $\rightarrow$ empty target | Cloned with independent target IDs | **PASS** |
| **Date Hygiene** | Assignment due date & quiz date windows | Reset to `NULL` | **PASS** |
| **Safe Mode Guard** | Cloning into populated target under `empty_only` | Aborts with descriptive error | **PASS** |
| **Append Mode** | Cloning into populated target under `append` | Appends with `display_order` offset | **PASS** |
| **Source Immutability** | Source course record count before and after | Exactly identical (untouched) | **PASS** |
| **Student Isolation** | Target course submissions, quiz attempts, answers, attendance | Exactly 0 records | **PASS** |
| **Rollback Safety** | Forced failure mid-transaction | Clean rollback; 0 residual records | **PASS** |
| **Audit Trail** | Activity logs table verification | Success and failure logged with stats | **PASS** |

**Summary: 59 passed, 0 failed.**
