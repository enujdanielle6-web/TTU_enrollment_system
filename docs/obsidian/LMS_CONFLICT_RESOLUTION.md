# LMS Admin Course Conflict Resolution (Duplicates & Orphans)

## Executive Summary

The **LMS Admin Conflict Resolution Engine** provides safe, conservative governance workflows to identify, diagnose, and resolve duplicate and orphan LMS course shells. 

```
   ══════════════════════════════════════════════════════════════════════════════
   HIGH-RISK DATA INTEGRITY GOVERNANCE BOUNDARY
   ══════════════════════════════════════════════════════════════════════════════
   [X] AUTOMATIC DESTRUCTIVE MERGING IS PROHIBITED BY POLICY.
   [X] NEVER AUTOMATICALLY MOVE OR MERGE STUDENT SUBMISSIONS.
   [X] NEVER ALTER OFFICIAL SECTIONS, SUBJECT CODES, CURRICULUM, OR ROSTERS.
   [X] PRESERVATION OVER DELETION: HISTORICAL STUDENT WORK IS IMMUTABLE.
   ══════════════════════════════════════════════════════════════════════════════
```

---

## 1. Architectural Domain Invariant

> **ENROLLMENT OWNS ACADEMIC TRUTH. LMS OWNS THE LEARNING EXPERIENCE.**

- **Enrollment / Registrar Domain**: Manages official academic sections (`college_sections`, `shs_sections`), subject catalogs (`subjects`), curricula (`curriculum`), official timetables (`college_section_subjects`, `shs_section_subjects`), and enrolled rosters (`college_enrollments`, `shs_enrollments`).
- **LMS Domain**: Manages digital instructional workspaces (`lms_courses`), syllabus chapters (`lms_modules`), lecture attachments (`lms_materials`), coursework tasks (`lms_assignments`), assessment quizzes (`lms_quizzes`), student submissions (`lms_submissions`), quiz attempts (`lms_quiz_attempts`), and attendance sessions (`lms_attendance_sessions`).
- **Conflict Boundary**: The LMS Admin may resolve LMS shell inconsistencies but **must never mutate official academic sections, subject codes, curriculum, or registrar records**.

---

## 2. Conflict Detection Rules

### 2.1 Course Shell Status Definitions

| State | Relational Definition | Operational Status |
| :--- | :--- | :--- |
| **Valid Shell** | Exists in `lms_courses`, maps to active section and subject, exists in official timetable (`college_section_subjects`), and is unique. | Active or Archived |
| **Duplicate Shells** | Multiple `lms_courses` records sharing the same `(academic_section_id, subject_id)` offering (e.g. across `academic_level` variations `'SHS'` vs `'Senior High School'`, legacy inserts, or manual generation anomalies). | High Inconsistency |
| **Orphan Shell** | An `lms_courses` record whose section no longer exists in `college_sections` / `shs_sections`, subject no longer exists in `subjects`, or whose offering was removed/cancelled from the official timetable schedule (`college_section_subjects` count = 0). | Detached Shell |
| **Unmapped Offering** | A section-subject scheduled in the official timetable that has no corresponding `lms_courses` shell. (Auto-provisioned by deterministic reconciliation). | Missing Shell |
| **Archived Shell** | Shell marked `status = 'archived'`. Preserves all student work, submissions, and grades in a read-only state. | Archived |

### 2.2 Duplicate Shell Forensic Detection
The conflict engine scans `lms_courses` grouping by `(academic_section_id, subject_id)` having `COUNT(*) > 1`. For each group:
- Assembles side-by-side dossiers for all conflicting shells.
- Measures instructional content: `modules_count`, `materials_count`, `assignments_count`, `quizzes_count`.
- Measures student artifacts: `submissions_count`, `quiz_attempts_count`, `quiz_answers_count`, `attendance_records_count`, `roster_count`.
- **Classification**:
  - `Manual Resolution Required`: Triggered when **$\ge 2$ shells contain student work**. Merging is strictly prohibited.
  - `Safe Archive Recommended`: Triggered when one shell is populated/primary while secondary shells are empty or inactive.

### 2.3 Orphan Shell Forensic Detection
The conflict engine scans `lms_courses` where:
1. `s.id IS NULL`: Subject code deleted from master catalog (`missing_subject`).
2. `cs.id IS NULL AND ss.id IS NULL`: Section deleted from directory (`missing_section`).
3. `timetable_offering_count = 0`: Class offering cancelled or removed from official semester timetable (`delisted_timetable`).

---

## 3. Supported Safe Resolution Actions vs Prohibited Actions

| Action | Safety Status | Execution Logic | Audit Trail Event |
| :--- | :--- | :--- | :--- |
| **`archive_duplicate`** | **SAFE (Recommended)** | Sets `lms_courses.status = 'archived'`. Preserves 100% of modules, submissions, attempts, and grades. Removes shell from active student view. | `LMS Course Shell Archived (Conflict Resolution)` |
| **`archive_orphan`** | **SAFE (Recommended)** | Sets `lms_courses.status = 'archived'`. Keeps historical student work and grades intact for compliance audits. | `LMS Course Shell Archived (Conflict Resolution)` |
| **`reassign_faculty`** | **SAFE** | Updates `lms_courses.faculty_user_id` to active faculty or aligns with timetable. | `LMS Faculty Reassigned` |
| **`flag_quarantine`** | **SAFE** | Records an administrative flag in `activity_logs` with investigation notes for Registrar or Academic Dean review. | `LMS Course Shell Flagged for Manual Review` |
| **`delete_empty_shell`** | **SAFE (Strictly Gated)** | Permitted **ONLY IF** shell has 0 modules, 0 materials, 0 assignments, 0 quizzes, 0 submissions, 0 attempts, and 0 attendance records. | `LMS Empty Course Shell Removed` |
| **`merge_shells`** | **UNSUPPORTED / PROHIBITED** | **STRICTLY REJECTED**. Throws policy exception. Relational schema cannot guarantee gradebook or submission integrity across course boundaries without risk of data corruption. | N/A (Blocked) |
| **Destructive Deletion of Shells with Work** | **UNSUPPORTED / PROHIBITED** | **STRICTLY REJECTED**. Throws safety gate exception: *"Destructive cleanup blocked: Shell contains student records."* | N/A (Blocked) |

---

## 4. Why Automatic Merge Is Prohibited by Institutional Policy

1. **Foreign Key Collisions**: `lms_submissions` references `assignment_id` (`fk_submission_assignment`). Re-parenting assignments or submissions across courses creates duplicate assignment prompts or breaks existing submission timestamps.
2. **Quiz Attempt Uniqueness**: `lms_quiz_attempts` enforces `UNIQUE(lms_quiz_id, student_id, attempt_number)`. Merging quizzes across shells causes duplicate key exceptions or overwrites student scores.
3. **Gradebook Distortion**: Student grades are computed dynamically from weighted submissions and attempts. Re-parenting work causes student GPAs to change, violating academic integrity.
4. **Conclusion**: When multiple shells contain student work, automatic merging is unsafe. The system flags the conflict as **"Manual Resolution Required"** and mandates administrative archiving.

---

## 5. UI Workflow

The interface is integrated into the **Enrollment ↔ LMS Synchronization Hub** (`/admin/lms/sync`):

```
[/admin/lms/sync]
  ├── Hero Strip: Run Safe Timetable Reconcile | Cloner | Dashboard
  ├── Status Cards: Healthy | Missing | Mismatches | Duplicates | Orphans
  └── Diagnostic Navigation Tabs:
        ├── Tab 1: Timetable Reconciliation (Missing Shells & Faculty Drift)
        ├── Tab 2: Duplicate Shell Conflicts (Forensic Side-by-Side Comparison)
        │     ├── Group Headers & Classification Badge
        │     ├── Course A vs Course B Metric Dossiers
        │     └── Safe Action Buttons (Archive, Flag, Safe Delete if Empty)
        └── Tab 3: Orphan Course Shells
              ├── Diagnostic Reason (Deleted Subject, Deleted Section, Delisted)
              ├── Instructional & Student Artifact Counters
              ├── Recommended Action Strategy
              └── Safe Action Buttons (Archive, Flag, Safe Delete if Empty)
```

---

## 6. Audit Trail Logging

Every resolution action logs immutably to `activity_logs` using `logActivity()`:
- `user_id`: Acting administrator user ID.
- `affected_record`: `'lms_courses:{course_id}'`.
- `icon`: `'bi-archive'`, `'bi-flag'`, or `'bi-trash'`.
- `title`: e.g. `'LMS Course Shell Archived (Conflict Resolution)'`.
- `description`: Contains course ID, artifact counts (modules, submissions, attempts), and administrator notes.
- `reason`: `'LMS Conflict Resolution: Safe Archive'`.

All events are immediately visible in the LMS Governance Audit Logs (`/admin/lms/audit_logs`).

---

## 7. Automated Test Matrix

Executed via `scripts/tests/test_lms_admin_conflict_resolution.php`:

| Test Category | Test Case | Expected Behavior | Result |
| :--- | :--- | :--- | :--- |
| **RBAC** | Admin / Superadmin access | Authorized | **PASS** |
| **RBAC** | Scheduler / Student / Guest access | HTTP 403 Forbidden | **PASS** |
| **Duplicate Empty Shells** | Group with 2 empty shells | Identified as duplicate; classified as `Safe Archive Recommended` | **PASS** |
| **Duplicate Empty Shells** | Archive redundant shell | Shell marked `archived`; primary remains `active` | **PASS** |
| **Duplicate with Content** | Group with populated shells | Correctly counts modules; archiving preserves all modules | **PASS** |
| **Duplicate with Submissions** | Shell A (submission) vs Shell B (attempt) | Flagged as `Manual Resolution Required` | **PASS** |
| **Duplicate with Submissions** | Attempt destructive deletion | Blocked with safety gate exception | **PASS** |
| **Duplicate with Submissions** | Attempt automatic merge | Blocked with policy exception | **PASS** |
| **Duplicate with Submissions** | Safe archive execution | Submissions and quiz attempts remain 100% intact | **PASS** |
| **Orphan Empty Shell** | Detached shell with 0 items | Identified as orphan; safe cleanup permitted | **PASS** |
| **Orphan Empty Shell** | Safe delete execution | Cleanly removed from `lms_courses` | **PASS** |
| **Orphan with Content** | Orphan with 2 modules | Deletion blocked; safe archive preserves modules | **PASS** |
| **Orphan with Student Work** | Orphan with submissions | Deletion strictly blocked; safe archive preserves work | **PASS** |
| **Quarantine Flag** | Administrative review note | Recorded in `activity_logs` with dean notes | **PASS** |
| **Merge Prohibition** | Direct merge invocation | Explicitly rejected to protect gradebook integrity | **PASS** |
| **Rollback Safety** | Forced mid-transaction failure | Clean rollback with zero data corruption | **PASS** |
| **Audit Logging** | Activity logs table verification | Full actor, course ID, and description logging | **PASS** |

**Summary: 45 passed, 0 failed.**
