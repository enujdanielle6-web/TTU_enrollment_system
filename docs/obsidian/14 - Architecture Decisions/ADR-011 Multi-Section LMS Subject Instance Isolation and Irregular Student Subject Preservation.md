# ADR-011: Multi-Section LMS Course Instance Isolation and Irregular Student Subject Preservation

## Status
**Accepted**

## Date
2026-09-13

## Context
As the TTU Enrollment System and LMS expanded to support higher student volumes, irregular student curriculums, and multi-section cohorts, two architectural challenges emerged:

### 1. Multi-Section Subject Crosstalk and Duplication in LMS
Multiple sections often share identical degree programs, curricula, and subjects. For instance, both Section `BSIT 1-A` and Section `BSIT 1-B` take the same first-year subjects (`CC101` Introduction to Computing, `CC102` Programming Fundamentals, `ENG101` Purposive Communication).
- **Previous Risk:** If LMS courses (`lms_courses`) are keyed solely to `subject_id`, students across multiple sections would either:
  1. Share the identical LMS classroom instance, resulting in gradebook crosstalk, shared announcements meant for a specific room/instructor, and mixed assignment submissions.
  2. Experience row duplication when joining `college_enrollments` against `lms_courses`, rendering duplicate subject cards on the student dashboard.
- **Architectural Requirement:** Each section must possess its own isolated `lms_courses` classroom instance with independent module contents, announcements, assignments, gradebooks, and attendance records, tied strictly via `(subject_id, section_id)`.

### 2. Irregular Student Subject Request Preservation
Irregular students customize their academic schedule by submitting specific subject requests (`application_subject_requests`) across different year levels or programs.
- **Previous Hazard:** During enrollment finalization (`EnrollmentService::finalizeEnrollment()`), the system previously resolved subjects by inspecting `college_section_subjects` belonging to the applicant's assigned `section_id`. For irregular students assigned to a baseline administrative section, this wiped or bypassed their custom approved subjects, forcing them into regular section offerings.
- **Tuition Assessment Disconnect:** In `AssessmentService::calculate()`, tuition unit totals and fee snapshot generation (`assessment_items`) previously calculated units based on regular curriculum defaults or section subject lists instead of inspecting the student's approved `application_subject_requests`.

---

## Decision

### 1. Strict Section Isolation in `lms_courses`
- In `lms_courses`, every course record explicitly couples `subject_id` and `section_id` (foreign key to `college_sections.id` or `shs_sections.id`), alongside a unique composite `course_code` (e.g. `CC101-BSIT1A` vs. `CC101-BSIT1B`).
- Student LMS course retrieval (`CollegeEnrollmentRepository`, `ShsEnrollmentRepository`, `LmsService`) joins the student's enrollment record on both keys:
  ```sql
  SELECT c.*, s.code as subject_code, s.name as subject_name, ...
  FROM college_enrollments ce
  JOIN lms_courses c 
    ON c.subject_id = ce.subject_id 
   AND (c.section_id = ce.college_section_id OR (c.section_id IS NULL AND ce.college_section_id IS NULL))
  WHERE ce.application_id = :app_id
  ```
- **Consequence:** Two students taking the same subject in different sections are routed to completely isolated classrooms. When Student A submits an assignment in Section 1-A, it is completely invisible to Section 1-B's faculty and gradebook.

### 2. Irregular Subject Preservation in Domain Services
- **`EnrollmentService::finalizeEnrollment()`**:
  Before querying regular section subjects, the matriculation orchestrator checks for approved custom subjects in `application_subject_requests`:
  ```php
  $stmt = $pdo->prepare("
      SELECT subject_id, section_id 
      FROM application_subject_requests 
      WHERE application_id = ? AND status = 'approved'
  ");
  $stmt->execute([$applicationId]);
  $customSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!empty($customSubjects)) {
      // Enroll directly into requested subjects without section overwrite
      foreach ($customSubjects as $req) {
          $ins = $pdo->prepare("
              INSERT INTO college_enrollments (application_id, subject_id, college_section_id, created_at)
              VALUES (?, ?, ?, NOW())
          ");
          $ins->execute([$applicationId, $req['subject_id'], $req['section_id'] ?? $application['college_section_id']]);
      }
  } else {
      // Fallback to regular section curriculum subjects
      self::assignSectionSubjects($applicationId, $sectionId, $level, $pdo);
  }
  ```
- **`AssessmentService::calculate()` & `snapshotAssessmentItems()`**:
  - Checks if `$application['student_type'] === 'irregular'` or if `application_subject_requests` exist.
  - Queries `application_subject_requests` to aggregate exact requested lecture/lab units.
  - Populates the snapshot array from the irregular student's custom subjects so that `assessment_items` permanently records the exact units billed.
  - Factored active scholarship grants (e.g., 100% tuition coverage or fixed grants) dynamically deducting from the computed tuition fee.

---

## Verification & Automated Test Suites

The isolation and subject preservation workflows are verified via automated end-to-end test bots:
1. **`scripts/test_two_sections_lms.php`**:
   - Creates Student A in `BSIT 1-A` and Student B in `BSIT 1-B`, enrolling both in `CC101`, `CC102`, and `ENG101`.
   - Verifies that Student A accesses Course IDs `1, 2, 3` and Student B accesses Course IDs `11, 12, 13`.
   - Confirms 0% course ID overlap and 0 duplicate cards.
2. **`scripts/test_irregular_scholarship_bot.php`**:
   - Enrolls an irregular student with 3 custom subjects (9 units) and a 100% Academic Excellence Scholarship.
   - Verifies ₱4,500.00 tuition discount deduction, net fee cashier verification, and enrollment finalization into exactly the 3 custom subjects without section subject overwrite.

---

## Consequences

### Positive
- **Complete Multi-Section Privacy:** Faculty assign grades and upload materials to specific section cohorts with zero leakage.
- **Accurate Financial Assessments:** Irregular students are billed strictly for the units they enroll in, and scholarship subsidies calculate accurately.
- **Zero Regression on Regular Cohorts:** Regular students continue seamless automated batch section subject population.

### Trade-offs
- Requires LMS seeders and schedule builders to provision distinct `lms_courses` records when new sections are created.

---
**Related:**
- [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]]
- [[ADR-010 Domain Service Layer Extraction and Atomic Sequences]]
- [[LMS]]
- [[LMS_Database_Architecture]]
- [[Registrar]]
- [[Scholarship]]
