# Cross-Module Data Flow & Table Sharing

This document maps how data crosses functional boundaries between administrative departments, applicant workflows, and the Learning Management System.

---

## 1. End-to-End Cross-Module Data Flow

```mermaid
flowchart TD
    subgraph 1. Onboarding & Verification
        Reg[Applicant Registration] -->|users: email_verified=0| OTP[6-Digit Email OTP]
        OTP -->|users: email_verified=1| AppForm[Application Form Submission]
        AppForm -->|applications: status=pending| Docs[Document Uploads: application_documents]
    end

    subgraph 2. Intake & Health Evaluation
        Docs --> AdmReview[Admissions Review]
        AdmReview -->|applications: status=approved| ClinicGate{Health Clearance}
        AppForm -->|health_records: status=pending| ClinicReview[Clinic Review]
        ClinicReview -->|health_records: status=verified| ClinicGate
    end

    subgraph 3. Curriculum, Scheduling & Finance
        ClinicGate -->|Approved & Medically Cleared| EnrollSelect[Subject & Section Enrollment]
        EnrollSelect -->|college_enrollments / shs_enrollments| Sched[Scheduler Matrix: Capacity Checked]
        EnrollSelect --> AssessMath[Finance Assessment Engine]
        Schol[Scholarship Grants] -->|Deducts discount| AssessMath
        AssessMath -->|student_assessments: net_amount| PayProof[Payment / Bank Slip Upload]
    end

    subgraph 4. Enrollment Finalization & LMS Activation
        PayProof --> Cashier[Cashier Records Payment: payment_records]
        Cashier -->|total_paid >= ₱3,000| Finalize[Finalize Enrollment: applications.status=enrolled]
        Finalize --> Creds[Generate Student Number & TTU Email]
        Creds --> Dispatch[PHPMailer Dispatches Welcome Email]
        Finalize --> LMSAuto[Repositories Auto-Provision lms_courses]
        LMSAuto --> LMSPortal[Student & Faculty LMS Active]
    end
```

---

## 2. Cross-Module Data Contracts

### 2.1 Admissions $\leftrightarrow$ Clinic
- **Connecting Entity:** `applications.id` $\leftrightarrow$ `health_records.application_id`.
- **Data Crossed:** Demographic information, medical conditions, physical measurements, emergency contacts.
- **Contract / Gate:** An applicant with `applications.status = 'approved'` cannot proceed to subject enrollment (`/applicant/enroll.php`) until a corresponding row in `health_records` exists with `status = 'verified'` (or submitted for clinic review).

### 2.2 Admissions $\leftrightarrow$ Registrar & Scheduler
- **Connecting Entities:**
  - `applications.college_curriculum_id` $\leftrightarrow$ `college_curricula.id`.
  - `applications.section_id` $\leftrightarrow$ `college_sections.id` (or `shs_sections.id`).
  - `college_enrollments` & `shs_enrollments`.
- **Data Crossed:** Degree program, strand, entry year level, section capacity, and subject list.
- **Contract:** When Admissions assigns a section or an applicant selects subjects, the controller writes to `college_enrollments` or `shs_enrollments`. The user's permanent `users.college_curriculum_id` is locked.

### 2.3 Scheduler & Registrar $\leftrightarrow$ Finance
- **Connecting Entities:** `college_enrollments` / `shs_enrollments` $\leftrightarrow$ `subjects.units` $\leftrightarrow$ `fee_templates` $\leftrightarrow$ `student_assessments`.
- **Data Crossed:** Total sum of enrolled subject units, lab subject flags.
- **Contract:** When `fee_templates.is_per_unit = 1`, `FinanceController` queries the enrollment bridge table, computes $\sum \text{units}$, and multiplies by `tuition_fee` rate per unit to populate `student_assessments.tuition_fee` and `student_assessments.net_amount`.

### 2.4 Scholarship $\leftrightarrow$ Finance
- **Connecting Entities:** `scholarships` $\leftrightarrow$ `scholarship_applications` $\leftrightarrow$ `scholarship_recipients` $\leftrightarrow$ `student_assessments.scholarship_id`.
- **Data Crossed:** Discount type (`percentage` vs `fixed`), coverage values (`tuition_coverage_value`, `misc_coverage_value`).
- **Contract:** When a scholarship grant is approved in `ScholarshipController`, it updates `student_assessments.discount_amount` and recalculates `net_amount = total_amount - discount_amount`.

### 2.5 Finance $\leftrightarrow$ Admissions & System Credential Dispatch
- **Connecting Entities:** `payment_records` $\leftrightarrow$ `student_assessments` $\leftrightarrow$ `applications` $\leftrightarrow$ `users`.
- **Data Crossed:** Accumulated payments (`total_paid`), official receipt numbers (`receipt_number`), student number (`student_number`), institutional email (`ttu_email`).
- **Contract:** When recorded payments satisfy `total_paid >= ₱3,000.00` (or full balance), `FinanceController` automatically invokes `finalizeStudentEnrollment()`, transitioning `applications.status = 'enrolled'`, generating `YYYY-XXXXXX` student number, generating `first.last@ttu.edu.ph`, and sending the welcome credentials email.

### 2.6 Enrollment $\leftrightarrow$ Learning Management System (LMS)
- **Connecting Entities:** `college_enrollments` / `shs_enrollments` $\leftrightarrow$ `CollegeEnrollmentRepository` / `ShsEnrollmentRepository` $\leftrightarrow$ `lms_courses` $\leftrightarrow$ `lms_modules`.
- **Data Crossed:** Active subject IDs, section codes, section adviser / instructor names.
- **Contract:** The LMS does not maintain separate course enrollment tables; it dynamically discovers a student's active subjects from `college_enrollments` / `shs_enrollments` where `applications.status = 'enrolled'`. On dashboard load, `CollegeEnrollmentRepository::getStudentCourses()` automatically creates or links `lms_courses` instances.

---

## 3. Shared Database Tables Intersect Matrix

| Database Table | Admissions | Clinic | Registrar | Scheduler | Finance | Scholarship | System Admin | LMS |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `users` | Read/Write | Read | Read | Read | Read | Read | Read/Write | Read |
| `applications` | Read/Write | Read | Read | Read | Read | Read | Read | Read |
| `health_records` | Read | Read/Write | - | - | - | - | - | - |
| `subjects` | Read | - | Read/Write | Read | Read | - | - | Read |
| `college_sections` / `shs_sections` | Read | - | Read | Read/Write | - | - | - | Read |
| `college_enrollments` / `shs_enrollments`| Read/Write | - | Read | Read/Write | Read | - | - | Read |
| `fee_templates` | Read | - | - | - | Read/Write | - | - | - |
| `student_assessments` | Read/Write | - | - | - | Read/Write | Read/Write | - | - |
| `payment_records` | - | - | - | - | Read/Write | - | - | - |
| `scholarships` & `scholarship_recipients`| - | - | - | - | Read | Read/Write | - | - |
| `activity_logs` | Write | Write | Write | Write | Write | Write | Read/Write | Write |
| `lms_*` (13 tables) | - | - | - | - | - | - | Read | Read/Write |

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[01 - Shared Dependencies & Impact Analysis]]
- [[04 - Database/Data Dictionary]]
