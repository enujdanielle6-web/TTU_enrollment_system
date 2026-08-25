# Entity Relationship Architecture & Data Models

This document provides a comprehensive visual and relational specification of the **Triple T University (TTU)** database (`sia`). It maps the entity-relationship topology across all functional domains, documents relationship cardinalities and foreign keys, explores the central **"Application as Term"** architecture, and details cross-domain data contracts.

---

## 1. High-Level System Domain Topology

The TTU relational schema is organized into 6 interconnected functional domains centered around the **Identity (`users`)** and **Application (`applications`)** hub:

```mermaid
flowchart TD
    Identity[1. Core Identity & Security Domain] -->|Owns / Authenticates| Admissions[2. Admissions & Health Domain]
    Identity -->|Enrolled In| Academic[3. Academic & Curriculum Domain]
    Identity -->|Assessed / Pays| Finance[4. Finance & Ledger Domain]
    Identity -->|Applies / Receives| Scholarship[5. Scholarship Domain]
    Identity -->|Teaches / Learns| LMS[6. Learning Management System Domain]

    Admissions -->|Anchors Term Records| Academic
    Admissions -->|Generates Billing Statement| Finance
    Academic -->|Supplies Enrolled Units| Finance
    Scholarship -->|Deducts Tuition Discounts| Finance
    Academic -->|Auto-Provisions Courses| LMS
```

---

## 2. The "Application as Term" Relational Paradigm

The central architectural tenet of the TTU database is: **There is no separate `students` table**.

```mermaid
erDiagram
    users ||--o{ applications : "submits per academic term"
    applications ||--o{ application_documents : "attaches admission files"
    applications ||--o{ health_records : "declares medical profile"
    applications ||--o{ college_enrollments : "enrolls in college subjects"
    applications ||--o{ shs_enrollments : "enrolls in SHS subjects"
    applications ||--o{ student_assessments : "generates term billing statement"
    student_assessments ||--o{ payment_records : "records payments & ORs"
```

### Relational Flow
1. **User Identity (`users`)**: Represents all human actors (`applicant`, `student`, `faculty`, `admissions`, `cashier`, `clinic`, `scheduler`, `admin`, `superadmin`).
2. **Term Application (`applications`)**: Represents an applicant's attempt or an enrolled student's registration for a specific academic school year and semester.
3. **Term-Bound Subsystems**:
   - **Academic Enrollments**: `college_enrollments` / `shs_enrollments` link active subjects directly to `applications.id`.
   - **Financial Ledger**: `student_assessments` links the term's billing statement to `applications.id`.
   - **Medical Clearance**: `health_records` connects health declarations to `applications.id`.
4. **Student State Transition**: An applicant becomes an active enrolled student when `applications.status = 'enrolled'` and `users.student_number` is populated (`YYYY-XXXXXX`).

---

## 3. Domain-Level Entity Relationship Diagrams

### 3.1 Core Identity & Security Domain

```mermaid
erDiagram
    users {
        int id PK
        varchar first_name
        varchar last_name
        varchar email UK
        varchar ttu_email
        varchar password
        varchar student_number UK
        enum role
        longtext permissions
        int college_curriculum_id FK
        tinyint email_verified
        varchar verification_code
        datetime verification_code_expires_at
        varchar reset_token
        datetime reset_token_expires_at
        tinyint force_password_reset
        tinyint is_active
    }

    activity_logs {
        int id PK
        int user_id FK
        varchar ip_address
        varchar affected_record
        varchar icon
        varchar title
        text description
        longtext old_value
        longtext new_value
        timestamp created_at
    }

    login_attempts {
        int id PK
        varchar ip_address
        varchar email
        timestamp attempted_at
    }

    system_settings {
        int id PK
        varchar setting_key UK
        text setting_value
    }

    announcements {
        int id PK
        varchar badge_label
        varchar badge_color
        varchar title
        text content
        tinyint is_active
    }

    users ||--o{ activity_logs : "generates audit entries"
```

---

### 3.2 Admissions & Health Domain

```mermaid
erDiagram
    users ||--o{ applications : "submits"
    users ||--o{ health_records : "owns"
    applications ||--o{ application_documents : "contains"
    applications ||--o{ health_records : "links"

    applications {
        int id PK
        int user_id FK
        varchar reference_number UK
        enum academic_level
        varchar grade_level
        varchar school_year
        varchar semester
        varchar student_type
        varchar strand
        int section_id
        int college_curriculum_id FK
        enum status
        enum document_submission_method
        text admin_feedback
        text internal_notes
    }

    application_documents {
        int id PK
        int application_id FK
        varchar document_name
        varchar file_path
        enum status
        text feedback
    }

    health_records {
        int id PK
        int user_id FK
        int application_id FK
        varchar height
        varchar weight
        varchar blood_type
        text medical_conditions
        varchar emergency_name
        varchar emergency_contact
        enum status
        text admin_remarks
    }
```

---

### 3.3 Academic & Curriculum Domain (College & SHS)

```mermaid
erDiagram
    college_programs ||--o{ college_curricula : "defines versions"
    college_programs ||--o{ college_sections : "groups into"
    college_curricula ||--o{ college_curriculum_subjects : "contains"
    college_curricula ||--o{ college_sections : "structures"
    subjects ||--o{ college_curriculum_subjects : "catalog entry"
    subjects ||--o{ college_section_subjects : "offered as"
    subjects ||--o{ college_enrollments : "enrolled in"
    college_sections ||--o{ college_section_subjects : "schedules"
    college_sections ||--o{ college_enrollments : "places student in"
    applications ||--o{ college_enrollments : "enrolls student"

    shs_strands ||--o{ shs_curricula : "defines versions"
    shs_strands ||--o{ shs_sections : "groups into"
    shs_curricula ||--o{ shs_curriculum_subjects : "contains"
    subjects ||--o{ shs_curriculum_subjects : "catalog entry"
    subjects ||--o{ shs_section_subjects : "offered as"
    subjects ||--o{ shs_enrollments : "enrolled in"
    shs_sections ||--o{ shs_section_subjects : "schedules"
    shs_sections ||--o{ shs_enrollments : "places student in"
    applications ||--o{ shs_enrollments : "enrolls student"

    college_programs {
        int id PK
        varchar code UK
        varchar name
        tinyint is_active
    }

    college_curricula {
        int id PK
        int program_id FK
        varchar curriculum_name
        varchar version
        varchar effective_academic_year
        enum status
    }

    college_curriculum_subjects {
        int id PK
        int curriculum_id FK
        int subject_id FK
        varchar year_level
        varchar semester
        int display_order
    }

    college_sections {
        int id PK
        varchar section_code UK
        int program_id FK
        int curriculum_id FK
        varchar year_level
        varchar semester
        int capacity
        varchar schedule_type
        varchar adviser
        tinyint status
    }

    college_section_subjects {
        int id PK
        int college_section_id FK
        int subject_id FK
        int capacity
        varchar day
        time start_time
        time end_time
        varchar room
        varchar instructor
    }

    college_enrollments {
        int id PK
        int application_id FK
        int subject_id FK
        int college_section_id FK
    }

    shs_strands {
        int id PK
        varchar code UK
        varchar name
        tinyint is_active
    }

    shs_curricula {
        int id PK
        int strand_id FK
        varchar curriculum_name
        varchar version
        varchar effective_academic_year
        enum status
    }

    shs_curriculum_subjects {
        int id PK
        int curriculum_id FK
        int subject_id FK
        varchar grade_level
        varchar semester
    }

    shs_sections {
        int id PK
        varchar section_code UK
        int strand_id FK
        varchar grade_level
        int capacity
        varchar adviser
        tinyint status
    }

    shs_section_subjects {
        int id PK
        int shs_section_id FK
        int subject_id FK
        int capacity
        varchar day
        time start_time
        time end_time
        varchar room
        varchar instructor
    }

    shs_enrollments {
        int id PK
        int application_id FK
        int subject_id FK
        int shs_section_id FK
    }

    subjects {
        int id PK
        varchar subject_code UK
        varchar subject_name
        int units
        varchar subject_type
        enum education_level
        tinyint status
    }
```

---

### 3.4 Finance, Cashier & Ledger Domain

```mermaid
erDiagram
    fee_templates ||--o{ student_assessments : "prices"
    scholarships ||--o{ student_assessments : "deducts grant"
    applications ||--o{ student_assessments : "statement for"
    users ||--o{ student_assessments : "billed to"
    student_assessments ||--o{ payment_records : "paid through"
    users ||--o{ payment_records : "paid by student / received by cashier"

    fee_templates {
        int id PK
        varchar name
        enum academic_level
        varchar grade_level
        varchar strand
        enum semester
        tinyint is_per_unit
        decimal tuition_fee
        decimal miscellaneous_fee
        decimal registration_fee
        decimal laboratory_fee
        decimal other_fees
        decimal total_amount
    }

    student_assessments {
        int id PK
        int user_id FK
        int application_id FK
        int fee_template_id FK
        int scholarship_id FK
        decimal tuition_fee
        decimal miscellaneous_fee
        decimal registration_fee
        decimal laboratory_fee
        decimal other_fees
        decimal total_amount
        decimal discount_amount
        decimal net_amount
        decimal total_paid
        enum payment_status
    }

    payment_records {
        int id PK
        int assessment_id FK
        int user_id FK
        int cashier_id FK
        decimal amount
        date payment_date
        varchar payment_method
        varchar receipt_number
        varchar reference_number
        varchar proof_image
        enum status
        text remarks
    }
```

---

### 3.5 Scholarship Domain

```mermaid
erDiagram
    college_programs ||--o{ scholarships : "eligible for"
    scholarships ||--o{ scholarship_applications : "applied for"
    scholarships ||--o{ scholarship_recipients : "awarded to"
    users ||--o{ scholarship_applications : "submits"
    users ||--o{ scholarship_recipients : "awarded student"
    scholarships ||--o{ student_assessments : "deducts from assessment"

    scholarships {
        int id PK
        varchar name
        varchar code UK
        enum category
        varchar provider
        int program_id FK
        varchar year_level
        decimal min_gwa
        enum tuition_coverage_type
        decimal tuition_coverage_value
        enum misc_coverage_type
        decimal misc_coverage_value
        decimal stipend_amount
        enum status
    }

    scholarship_applications {
        int id PK
        int user_id FK
        int scholarship_id FK
        varchar academic_year_id
        varchar semester
        enum status
        longtext submitted_documents
        text admin_feedback
    }

    scholarship_recipients {
        int id PK
        int user_id FK
        int scholarship_id FK
        varchar academic_year_id
        varchar semester
        varchar status
    }
```

---

### 3.6 Learning Management System (LMS) Domain

```mermaid
erDiagram
    subjects ||--o{ lms_courses : "course subject"
    users ||--o{ lms_courses : "taught by instructor"
    lms_courses ||--o{ lms_modules : "contains chapters"
    lms_modules ||--o{ lms_materials : "contains files"
    lms_courses ||--o{ lms_assignments : "has tasks"
    lms_modules ||--o{ lms_assignments : "attached to"
    lms_assignments ||--o{ lms_submissions : "submitted by students"
    users ||--o{ lms_submissions : "student submitter / faculty grader"
    lms_courses ||--o{ lms_quizzes : "has evaluations"
    lms_quizzes ||--o{ lms_questions : "contains questions"
    lms_questions ||--o{ lms_question_choices : "has options"
    lms_quizzes ||--o{ lms_quiz_attempts : "attempted by students"
    users ||--o{ lms_quiz_attempts : "student attempt"
    lms_quiz_attempts ||--o{ lms_quiz_answers : "records choices"
    lms_courses ||--o{ lms_attendance_sessions : "holds class meetings"
    lms_attendance_sessions ||--o{ lms_attendance_records : "logs roll call"
    users ||--o{ lms_attendance_records : "student attendance"
    lms_courses ||--o{ lms_announcements : "broadcasts notices"
    users ||--o{ lms_announcements : "posted by author"

    lms_courses {
        int id PK
        int subject_id FK
        int faculty_user_id FK
        varchar course_code UK
        varchar course_name
        varchar term
        varchar academic_year
        enum status
    }

    lms_modules {
        int id PK
        int lms_course_id FK
        varchar title
        text description
        int order_index
        enum status
    }

    lms_materials {
        int id PK
        int lms_module_id FK
        varchar title
        varchar file_path
        varchar file_type
        enum status
    }

    lms_assignments {
        int id PK
        int lms_course_id FK
        int lms_module_id FK
        varchar title
        text description
        datetime due_date
        decimal max_points
        varchar file_path
        enum status
    }

    lms_submissions {
        int id PK
        int assignment_id FK
        int student_id FK
        int graded_by FK
        varchar file_path
        text submission_text
        decimal grade
        text feedback
        datetime submitted_at
        datetime graded_at
    }

    lms_quizzes {
        int id PK
        int lms_course_id FK
        varchar title
        int time_limit_minutes
        decimal passing_score
        datetime due_date
        enum status
    }

    lms_questions {
        int id PK
        int lms_quiz_id FK
        text question_text
        enum question_type
        decimal points
        int order_index
    }

    lms_question_choices {
        int id PK
        int lms_question_id FK
        text choice_text
        tinyint is_correct
        int order_index
    }

    lms_quiz_attempts {
        int id PK
        int lms_quiz_id FK
        int student_id FK
        int attempt_number
        decimal score
        decimal total_points
        tinyint passed
        datetime started_at
        datetime completed_at
    }

    lms_quiz_answers {
        int id PK
        int lms_quiz_attempt_id FK
        int lms_question_id FK
        int lms_question_choice_id FK
        text text_answer
        tinyint is_correct
        decimal points_awarded
    }

    lms_attendance_sessions {
        int id PK
        int lms_course_id FK
        date session_date
        varchar title
    }

    lms_attendance_records {
        int id PK
        int lms_attendance_session_id FK
        int student_id FK
        enum status
        text remarks
    }

    lms_announcements {
        int id PK
        int lms_course_id FK
        int author_user_id FK
        varchar title
        text content
    }
```

---

## 4. Key Relationships & Foreign Key Reference

| Parent Table | Child Table | Cardinality | Foreign Key Constraint | Action on Delete | Business Meaning |
|---|---|:---:|---|---|---|
| `users` | `applications` | $1 : N$ | `applications.user_id` $\rightarrow$ `users.id` | `CASCADE` | Tracks all admission and term enrollment records for a person. |
| `applications` | `application_documents`| $1 : N$ | `application_documents.application_id` $\rightarrow$ `applications.id` | `CASCADE` | Digital admission requirement files (PSA, Form 137, Good Moral). |
| `applications` | `health_records` | $1 : 1$ | `health_records.application_id` $\rightarrow$ `applications.id` | `CASCADE` | Medical history declarations and clinic clearance status. |
| `college_programs`| `college_curricula` | $1 : N$ | `college_curricula.program_id` $\rightarrow$ `college_programs.id` | `CASCADE` | Versioned curriculum blueprints under a degree program. |
| `college_curricula`| `college_curriculum_subjects` | $1 : N$ | `college_curriculum_subjects.curriculum_id` $\rightarrow$ `college_curricula.id` | `CASCADE` | Maps subjects to specific year levels and semesters. |
| `college_sections`| `college_section_subjects` | $1 : N$ | `college_section_subjects.college_section_id` $\rightarrow$ `college_sections.id` | `CASCADE` | Timetable schedule slots (day, time, room, instructor) per section block. |
| `applications` | `college_enrollments` | $1 : N$ | `college_enrollments.application_id` $\rightarrow$ `applications.id` | `CASCADE` | Official bridge table assigning college subjects to enrolled students. |
| `shs_strands` | `shs_curricula` | $1 : N$ | `shs_curricula.strand_id` $\rightarrow$ `shs_strands.id` | `CASCADE` | Versioned curriculum blueprints for Senior High School strands. |
| `shs_sections` | `shs_enrollments` | $1 : N$ | `shs_enrollments.shs_section_id` $\rightarrow$ `shs_sections.id` | `CASCADE` | Official bridge table assigning SHS subjects to enrolled students. |
| `fee_templates` | `student_assessments` | $1 : N$ | `student_assessments.fee_template_id` $\rightarrow$ `fee_templates.id` | `SET NULL` | Pricing template used to compute student tuition and misc fees. |
| `student_assessments`| `payment_records`| $1 : N$ | `payment_records.assessment_id` $\rightarrow$ `student_assessments.id` | `CASCADE` | Over-the-counter payments and bank transfer proof transactions. |
| `scholarships` | `scholarship_recipients`| $1 : N$ | `scholarship_recipients.scholarship_id` $\rightarrow$ `scholarships.id` | `CASCADE` | Active student grantees receiving financial deductions. |
| `subjects` | `lms_courses` | $1 : N$ | `lms_courses.subject_id` $\rightarrow$ `subjects.id` | `CASCADE` | Active LMS course provision instances for enrolled subjects. |
| `lms_courses` | `lms_modules` | $1 : N$ | `lms_modules.lms_course_id` $\rightarrow$ `lms_courses.id` | `CASCADE` | Weekly learning modules and syllabus units. |
| `lms_modules` | `lms_materials` | $1 : N$ | `lms_materials.lms_module_id` $\rightarrow$ `lms_modules.id` | `CASCADE` | Downloadable lecture files, PDFs, and learning assets. |
| `lms_courses` | `lms_assignments` | $1 : N$ | `lms_assignments.lms_course_id` $\rightarrow$ `lms_courses.id` | `CASCADE` | Graded tasks, instructions, and due dates. |
| `lms_assignments` | `lms_submissions` | $1 : N$ | `lms_submissions.assignment_id` $\rightarrow$ `lms_assignments.id` | `CASCADE` | Student file uploads, grades, and faculty feedback. |
| `lms_courses` | `lms_quizzes` | $1 : N$ | `lms_quizzes.lms_course_id` $\rightarrow$ `lms_courses.id` | `CASCADE` | Timed evaluation assessments. |
| `lms_quizzes` | `lms_questions` | $1 : N$ | `lms_questions.lms_quiz_id` $\rightarrow$ `lms_quizzes.id` | `CASCADE` | Question bank items (Multiple Choice, True/False, Essay). |
| `lms_questions` | `lms_question_choices` | $1 : N$ | `lms_question_choices.lms_question_id` $\rightarrow$ `lms_questions.id` | `CASCADE` | Multiple choice option texts and `is_correct` answer flags. |
| `lms_quizzes` | `lms_quiz_attempts` | $1 : N$ | `lms_quiz_attempts.lms_quiz_id` $\rightarrow$ `lms_quizzes.id` | `CASCADE` | Timed student exam attempts and final scores. |
| `lms_courses` | `lms_attendance_sessions`| $1 : N$ | `lms_attendance_sessions.lms_course_id` $\rightarrow$ `lms_courses.id` | `CASCADE` | Class meeting date logs. |
| `lms_attendance_sessions`| `lms_attendance_records`| $1 : N$ | `lms_attendance_records.lms_attendance_session_id` $\rightarrow$ `lms_attendance_sessions.id` | `CASCADE` | Individual student roll-call status (`present`, `late`, `absent`, `excused`). |

---

## 5. Cross-Domain Indirect & Polymorphic Bridges

Certain key interactions in the application are polymorphic or indirect:

### 1. Dynamic LMS Course Discovery (Polymorphic Bridge)
- `college_enrollments` connects to `applications` $\rightarrow$ `subjects` $\rightarrow$ `college_sections`.
- `shs_enrollments` connects to `applications` $\rightarrow$ `subjects` $\rightarrow$ `shs_sections`.
- **Indirect Bridge:** The LMS does not have a static student enrollment table; `CollegeEnrollmentRepository` and `ShsEnrollmentRepository` query these enrollment bridge tables and dynamically resolve `lms_courses.id` where `applications.status = 'enrolled'`.

### 2. Tuition Rate per Unit Calculation (Indirect Aggregation)
- `student_assessments` references `fee_templates`.
- When `fee_templates.is_per_unit = 1`, the controller computes:
  $$\text{Total Tuition} = \left(\sum \text{subjects.units} \text{ from } \text{college\_enrollments}\right) \times \text{fee\_templates.tuition\_fee}$$
- The calculation is computed on-the-fly in PHP before persisting to `student_assessments`.

---

## 6. Legacy & Uncertain Entities Investigation

### Status of `student_scholarships` Table

```text
Table Name: student_scholarships
Columns: id, assessment_id, scholarship_id, academic_year, semester, created_at, updated_at
Foreign Keys:
  - fk_student_scholarships_assessment (assessment_id -> student_assessments.id)
  - fk_student_scholarships_scholarship (scholarship_id -> scholarships.id)
```

#### Codebase Evidence & Audit Finding:
1. **Grep Search in Executable Code (`app/`)**:
   - `student_scholarships` is **NOT referenced in any active PHP Controller, Model, Service, Repository, or View**.
2. **Active Implementation**:
   - Active scholarship grants are stored in **`scholarship_recipients`** (`ScholarshipController.php:257-270`) and tracked via **`scholarship_applications`**.
   - Active discounts are stored directly as a foreign key on **`student_assessments.scholarship_id`**.
3. **Classification**:
   - **CONFIRMED LEGACY / SUPERSEDED ARTIFACT**: `student_scholarships` was the early association table in early prototypes, superseded by `scholarship_recipients` during the financial refactoring. It remains present in `schema_dump.sql` to prevent breaking legacy installations, but is not used in active execution.

---

## 7. Database View: `student_academic_records_view`

The active MariaDB database includes a pre-compiled database View:
```sql
CREATE VIEW student_academic_records_view AS
SELECT 
    u.id AS user_id,
    u.student_number,
    u.first_name,
    u.last_name,
    u.email,
    u.ttu_email,
    a.id AS application_id,
    a.reference_number,
    a.academic_level,
    a.grade_level,
    a.strand,
    a.status AS enrollment_status,
    sec.section_code,
    (SELECT SUM(s.units) 
     FROM college_enrollments ce 
     JOIN subjects s ON ce.subject_id = s.id 
     WHERE ce.application_id = a.id) AS total_enrolled_units
FROM users u
JOIN applications a ON u.id = a.user_id
LEFT JOIN college_sections sec ON a.section_id = sec.id;
```
**Purpose**: Synthesizes student demographic, enrollment state, section codes, and unit calculations for reporting controllers (`ReportController.php`, `RegistrarController.php`).

---
**Related Documentation:**
- [[Database Overview]]
- [[Data Dictionary]]
- [[Users Table]]
- [[Applications Table]]
- [[16 - Page Relationships/02 - Cross-Module Data Flow & Table Sharing]]
- [[02 - Modules/LMS_Database_Architecture]]
