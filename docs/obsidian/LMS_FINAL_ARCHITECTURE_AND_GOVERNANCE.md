# TTU LEARNING MANAGEMENT SYSTEM (LMS) FINAL ARCHITECTURE & GOVERNANCE SPECIFICATION

> **Institutional System**: Triple T University (TTU) Academic & Learning Management System  
> **Subsystem**: Learning Management System (LMS) & Enrollment Integration Engine  
> **Repository Workspace**: `c:\xampp\htdocs\sia`  
> **Final Status**: **PRODUCTION-READY (RELEASE GATE: PASS)**  
> **Verification**: 126 / 126 Automated Verification Tests Passing Across All 6 Phases (100%)  
> **Governing Architectural Principle**:  
> **ENROLLMENT OWNS ACADEMIC TRUTH. LMS OWNS THE LEARNING EXPERIENCE.**

---

## 1. DOMAIN BOUNDARIES & CORE PHILOSOPHY

The TTU system enforces a strict unidirectional derivation boundary between the **Enrollment System** and the **Learning Management System (LMS)**. Under no circumstances does the LMS become a secondary Registrar system.

```text
┌────────────────────────────────────────────────────────────────────────┐
│                   ENROLLMENT SUBSYSTEM (SOURCE OF TRUTH)               │
├────────────────────────────────────────────────────────────────────────┤
│  • Student Academic Identity (`users.student_number`, `applications`)  │
│  • Academic Hierarchy: College Programs, SHS Strands, Curricula        │
│  • Official Course Catalog & Subjects (`subjects`)                     │
│  • Official Section Definitions (`college_sections`, `shs_sections`)  │
│  • Official Timetable & Faculty Scheduling                             │
│    (`college_section_subjects`, `shs_section_subjects`)                │
│  • Official Enrollment Statuses                                        │
│    (`college_enrollments`, `shs_enrollments` [enrolled/dropped/withdrawn])│
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                       Explicit Provisioning & Reconcile
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                 LMS SUBSYSTEM (LEARNING EXPERIENCE LAYER)              │
├────────────────────────────────────────────────────────────────────────┤
│  • Course Classroom Shells (`lms_courses`)                             │
│  • Instructional Modules (`lms_modules`)                               │
│  • Canonical Learning Materials (`lms_materials`)                      │
│  • Assessment Tasks & Deliverables (`lms_assignments`)                 │
│  • Student Work Submissions (`lms_submissions`)                        │
│  • Online Quizzes & Question Banks (`lms_quizzes`, `lms_questions`)    │
│  • Timed Student Attempts & Scores (`lms_quiz_attempts`)               │
│  • Formative Learning Gradebook (Calculated dynamically)               │
│  • Daily Course Attendance Records (`lms_attendance`)                  │
│  • Course Announcements & Updates (`lms_announcements`)                │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. AUTHENTICATION & SESSION ARCHITECTURE

### 2.1 Multi-Portal Authentication
1. **Applicant / Student Enrollment Portal** (`/sia/auth/login.php`): Authenticates applicants, admitted students, and administrative staff into the official Enrollment System.
2. **LMS Student Dedicated Portal** (`/sia/auth/lms_student_login.php`): Specialized login gate strictly for enrolled learners.
3. **LMS Faculty Dedicated Portal** (`/sia/auth/lms_faculty_login.php`): Specialized login gate for instructional faculty.

### 2.2 Strict Enrollment Gating
Only users with verified official registrar enrollments are permitted into the Student LMS:
```sql
SELECT COUNT(*) FROM applications WHERE user_id = :uid AND status = 'enrolled';
```
* **Status = 'draft' / 'submitted' / 'under_review'**: Rejected with guidance to complete application.
* **Status = 'approved'**: Explicitly rejected with prompt: *"Your application is approved, but official enrollment is not finalized. Please complete cashier payment and registrar finalization."*
* **Status = 'enrolled'**: Successfully issued session and redirected to LMS dashboard.

### 2.3 Session Integrity & Hygiene
All authentication routines regenerate session IDs upon login (`session_regenerate_id(true)`), record client IP and user agent in `SessionSecurityMiddleware`, and set canonical session keys:
- `$_SESSION['user_id']` (integer)
- `$_SESSION['user_role']` (`'student'`, `'faculty'`, `'admin'`, `'superadmin'`, etc.)
- `$_SESSION['lms_status']` (`'active'`, `'suspended'`, `'inactive'`)
- `$_SESSION['lms_logged_in']` (boolean true)

---

## 3. AUTHORIZATION & ROLE-BASED ACCESS CONTROL (RBAC)

### 3.1 Middleware Pipeline
Every web request traverses the core security pipeline in [`app/Routes/web.php`](file:///c:/xampp/htdocs/sia/app/Routes/web.php):
1. [`SessionSecurityMiddleware`](file:///c:/xampp/htdocs/sia/app/Middleware/SessionSecurityMiddleware.php): Validates session hijacking, user agent continuity, and session expiration.
2. [`CsrfMiddleware`](file:///c:/xampp/htdocs/sia/app/Middleware/CsrfMiddleware.php): Enforces CSRF token check on `POST`, `PUT`, `DELETE` methods.
3. [`AuthMiddleware`](file:///c:/xampp/htdocs/sia/app/Middleware/AuthMiddleware.php): Requires active authentication (`$_SESSION['logged_in']`).
4. [`RoleMiddleware`](file:///c:/xampp/htdocs/sia/app/Middleware/RoleMiddleware.php): Enforces role requirements (`student`, `faculty`, `admin`).

### 3.2 Complete Authorization Matrix

| Subsystem / Operation | Student Role | Faculty Role | LMS Admin Role | Unrelated Roles (Cashier, Clinic, etc.) |
| :--- | :--- | :--- | :--- | :--- |
| **Student LMS Dashboard & Courses** | **YES** | NO (Redirected) | NO (Admin View Only) | NO (Redirected) |
| **Course Materials Download** | **YES (Enrolled)** | **YES (Assigned)** | **YES (Inspection)** | NO (HTTP 403) |
| **Assignment Submission** | **YES (Enrolled)** | NO | NO | NO (HTTP 403) |
| **Quiz Taker** | **YES (Enrolled)** | NO | NO | NO (HTTP 403) |
| **Personal Gradebook** | **YES (Own Only)** | NO | NO | NO (HTTP 403) |
| **Faculty LMS Course Authoring** | NO (HTTP 403) | **YES (Assigned)** | NO | NO (HTTP 403) |
| **Faculty Gradebook & Submission Grading** | NO (HTTP 403) | **YES (Assigned)** | NO | NO (HTTP 403) |
| **LMS Admin Governance Dashboard** | NO (HTTP 403) | NO (HTTP 403) | **YES** | NO (HTTP 403) |
| **LMS Course Deep Inspection** | NO (HTTP 403) | NO (HTTP 403) | **YES** | NO (HTTP 403) |
| **Synchronized Faculty Reassignment** | NO (HTTP 403) | NO (HTTP 403) | **YES** | NO (HTTP 403) |
| **LMS User Access Governance** | NO (HTTP 403) | NO (HTTP 403) | **YES** | NO (HTTP 403) |
| **Enrollment Synchronization & Reconcile** | NO (HTTP 403) | NO (HTTP 403) | **YES** | NO (HTTP 403) |
| **Academic Term Archival** | NO (HTTP 403) | NO (HTTP 403) | **YES** | NO (HTTP 403) |
| **LMS Audit Logs Access** | NO (HTTP 403) | NO (HTTP 403) | **YES** | NO (HTTP 403) |

---

## 4. COURSE PROVISIONING & RESOLUTION PIPELINE

### 4.1 Idempotent, Explicit Course Shell Creation
Course shells are created **exclusively during write operations** (Schedule creation in `SchedulerController`, Registrar finalization in `RegistrarController`, or explicit Admin reconciliation in `LmsAdminService`).
- Table: [`lms_courses`](file:///c:/xampp/htdocs/sia/database/schema.sql#L430)
- Uniqueness Constraint: `UNIQUE KEY unique_section_subject (academic_level, academic_section_id, subject_id)`
- Provisioning Query:
```sql
INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
VALUES (:lvl, :sec_id, :sub_id, :fac_id, 'active')
ON DUPLICATE KEY UPDATE 
    faculty_user_id = IF(VALUES(faculty_user_id) IS NOT NULL, VALUES(faculty_user_id), faculty_user_id),
    status = 'active';
```
- **Read-Only Purity**: Repository and controller `GET` methods never execute `INSERT` statements.

### 4.2 Dynamic Student Course Resolution
Student LMS courses are dynamically resolved from live registrar enrollment rows where `status = 'enrolled'`. No duplicate `lms_enrollments` table exists:
- College: [`college_enrollments`](file:///c:/xampp/htdocs/sia/database/schema.sql#L377) $\bowtie$ [`lms_courses`](file:///c:/xampp/htdocs/sia/database/schema.sql#L430)
- SHS: [`shs_enrollments`](file:///c:/xampp/htdocs/sia/database/schema.sql#L349) $\bowtie$ [`lms_courses`](file:///c:/xampp/htdocs/sia/database/schema.sql#L430)
- Irregular Students: Dynamically resolve subject-specific course shells without requiring a fixed block section.

### 4.3 Multi-Section Instance Isolation
Course shells are partitioned by `(academic_level, academic_section_id, subject_id)`. Two sections taking the same academic subject (e.g., BSIT 1-A and BSIT 1-B taking CC101) receive completely separate rows in `lms_courses`. Modules, materials, assignments, quizzes, and rosters are completely isolated.

---

## 5. ENROLLMENT ↔ LMS LIFECYCLE INTEGRATION

| Event | Enrollment Action | LMS Consequence | Data Preservation |
| :--- | :--- | :--- | :--- |
| **New Enrollment** | `applications.status = 'enrolled'` | Resolves section course shells; activates student access | Active learning begins |
| **Subject Drop** | `college_enrollments.status = 'dropped'`, `dropped_at = NOW()` | Access revoked immediately; student hidden from active gradebook and roster | Submissions, quiz attempts, and grades preserved intact |
| **Subject Withdrawal** | `college_enrollments.status = 'withdrawn'`, `dropped_at = NOW()` | Access revoked immediately; student hidden from active gradebook and roster | Submissions, quiz attempts, and grades preserved intact |
| **Section Transfer** | Atomic update of `applications.section_id` and `college_enrollments.college_section_id` | Old section shell access revoked; new section shell access granted dynamically | Prior section student work preserved in historical records |
| **Faculty Reassignment** | Update `college_section_subjects.faculty_user_id` | LMS course ownership swaps to new faculty member; old faculty access revoked | Course content, materials, assignments, and grades remain 100% intact |
| **Unassigned Shell (TBA)** | `faculty_user_id = NULL` | Course loads cleanly as "Instructor TBA"; student access remains functional; arbitrary faculty claims rejected | Content preserved intact |
| **Academic Term Archival** | Term concludes in academic calendar | `lms_courses.status = 'archived'`; courses become read-only historical archives | All modules, materials, assignments, submissions, and attempts retained |

---

## 6. SYNCHRONIZATION & RECONCILIATION ENGINE

Located in [`app/Services/LmsAdminService.php`](file:///c:/xampp/htdocs/sia/app/Services/LmsAdminService.php):
1. **Diagnostic Scan (`scanEnrollmentSync`)**: Scans all active section subjects in official timetable truth against `lms_courses`.
   - Identifies missing course shells.
   - Identifies faculty mismatches between timetable and LMS course owner.
   - Identifies duplicate course shells.
   - Identifies orphan course shells.
2. **Deterministic Auto-Reconcile (`reconcileAllDeterministic`)**:
   - Provisions missing shells in an isolated PDO transaction.
   - Aligns faculty assignments to authoritative timetable.
   - **Safely preserves ambiguous conflicts**: Duplicates and orphans are reported for human administrative inspection rather than destructively deleted.

---

## 7. SECURE FILE STORAGE & STREAMING ARCHITECTURE

### 7.1 Canonical Storage Hierarchy
All uploaded files are stored outside the public document root:
- Learning Materials: `storage/uploads/lms/materials/`
- Assignment Submissions: `storage/uploads/lms/submissions/`

### 7.2 Defense-in-Depth Protections
1. **Root Rewrite Block**: [`c:\xampp\htdocs\sia\.htaccess`](file:///c:/xampp/htdocs/sia/.htaccess) line 12 contains `RewriteRule ^(app|config|database|storage)/ - [F,L]`.
2. **Storage Subdirectory .htaccess**: [`c:\xampp\htdocs\sia\storage\.htaccess`](file:///c:/xampp/htdocs/sia/storage/.htaccess) enforces `Require all denied` and `Options -Indexes -ExecCGI`.
3. **Strict Extension Blacklist**: Rejects `php`, `phtml`, `phar`, `exe`, `bat`, `cmd`, `sh`, `py`, `js`, `vbs`, `html`, `htm`.
4. **MIME Verification**: Validated on disk via `finfo_file(FILEINFO_MIME_TYPE)` and `mime_content_type()`.
5. **Randomized Storage Names**: Files stored with hashed random names (`mat_{course}_{module}_{time}_{random}.{ext}`).
6. **Streaming Controller**: Files stream exclusively through [`DownloadController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Lms/DownloadController.php) after verifying session authentication, course enrollment, and ownership.

---

## 8. ADMINISTRATIVE AUDIT LOGGING

Administrative actions are recorded in [`activity_logs`](file:///c:/xampp/htdocs/sia/database/schema.sql#L415) using [`logActivity()`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php#L386):
* LMS Course Creation (`bi-mortarboard`)
* LMS Faculty Reassignment (`bi-person-badge`)
* LMS Course Status Changes (`bi-archive`)
* LMS Term Bulk Archival (`bi-archive-fill`)
* LMS User Access Suspension / Activation (`bi-shield-lock`)
* LMS Enrollment Reconciliation (`bi-arrow-repeat`)

Protected by `enforceAdminAccess()`; students, faculty, and unauthorized staff are blocked from viewing audit logs.
