# Database Schema

The `sia` database consists of roughly 20 interconnected tables governing users, applications, academics, and finances. 

## 1. Identity & Auth
- **`users`**: The central auth table for both staff and applicants. Contains `role` (`superadmin`, `applicant`, `admissions`, `registrar`, `clinic`, `cashier`, `scholarship`).
- **`activity_logs`**: Tracks every significant action performed by any `user_id`.

## 2. Admissions
- **`applications`**: The core entity representing a student's intent to enroll. Linked to `users` via `user_id`. Tracks `academic_level` (SHS/College), `strand` / `program`, and `status`.
- **`applicant_documents`**: Files uploaded by applicants to satisfy requirements (PSA, Report Card, etc.).
- **`medical_records`**: Health status and clearance linked to `user_id`. 

## 3. Registrar (Academics)
- **`college_programs` & `shs_strands`**: The offerings of the university.
- **`college_subjects` & `shs_subjects`**: The catalog of available courses.
- **`college_curriculum` & `shs_curriculum`**: Maps subjects to programs across specific year levels and semesters.
- **`college_sections` & `shs_sections`**: Groupings of students. Each section is tied to a specific curriculum.
- **`college_enrollments` & `shs_enrollments`**: The final canonical record that a student (via `application_id`) is enrolled in a specific `section_id` for an academic year.

## 4. Finance
- **`fee_templates`**: Predefined pricing structures (Tuition, Misc, Lab) mapped to specific academic levels or programs.
- **`assessments`**: The generated bill for an `application_id`, containing `total_amount`, `discount_amount`, and `net_amount`.
- **`payments`**: Individual transactions (cash or online) applied toward an `assessment_id`.

## 5. Scholarships
- **`scholarships`**: Available grants (e.g., Academic, Athletic) with associated discount percentages or fixed amounts.
- **`scholarship_applications`**: A student's request for a scholarship, linked to `user_id` and `scholarship_id`.

## Entity Relationship Flow
```text
User (Applicant)
 └── Application
      ├── Documents
      ├── Medical Record
      ├── Scholarship Application
      ├── Assessment
      │    └── Payments
      └── Enrollment (College/SHS)
           └── Section
                └── Curriculum
                     └── Subjects
```

*Related:* [[Enrollment Workflow]]
