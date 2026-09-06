# Student Lifecycle Workflow

This document outlines the complete, end-to-end operational lifecycle of a student in the TTU Enrollment System and LMS, across all departments and roles.

---

## 1. Complete Student Lifecycle Map

```mermaid
flowchart TD
    subgraph Phase 1: Onboarding & Identity
        A1[1. Public Registration: /auth/register.php] --> A2[2. 6-Digit Email OTP Verification: /auth/verify_email.php]
        A2 --> A3[3. Admission Application Form: /applicant/enroll.php]
        A3 --> A4[4. Document Requirements Upload: /applicant/documents.php]
        A4 --> A5[5. Health Information Submission: /applicant/health_info.php]
    end

    subgraph Phase 2: Administrative Evaluation
        A4 --> B1[6. Admissions Review & Document Verification: /admin/admissions/]
        A5 --> B2[7. Clinic Evaluation & Medical Clearance: /admin/clinic/]
        B1 -->|Admissions Approved| C1{Both Cleared?}
        B2 -->|Clinic Cleared| C1
    end

    subgraph Phase 3: Academic Enrollment & Assessment
        C1 -->|Yes| D1[8. Subject Schedule & Section Selection: /applicant/enroll.php]
        D1 --> D2[9. AssessmentService Freezes assessment_items Snapshots]
        D2 --> D3[10. Cashier Verifies Payment: applications.status = payment_verified]
    end

    subgraph Phase 4: Registrar Final Admission & Credential Provisioning
        D3 --> E1[11. Registrar Finalizes Enrollment: /admin/registrar/finalize_enrollment.php]
        E1 --> E2[12. StudentNumberService Generates Atomic ID & TTU Email]
        E2 --> E3[13. Dispatch Welcome Credentials Email & Set force_password_reset = 1]
        E3 --> E4[14. Auto-Provision Subject Enrollments & LMS Courses]
    end

    subgraph Phase 5: LMS Academic Delivery
        E4 --> F1[15. Student Logs in to LMS with Student Number & Password]
        F1 --> F2[16. View Learning Modules, Take Quizzes, Submit Assignments, View Gradebook]
    end
```

---

## 2. Detailed Phase Breakdown

### Phase 1: Onboarding & Identity
- **Actor:** Public User $\rightarrow$ `applicant`.
- **Key Actions:** Account registration, 6-digit email OTP verification, demographic form submission, scanned document upload (PSA, Form 137), and health history declaration.

### Phase 2: Administrative Evaluation
- **Actors:** Admissions Officers (`admissions`), Clinic Officers (`clinic`).
- **Key Actions:** Admissions inspects uploaded certificate files and application data; Clinic reviews health conditions and emergency contacts. Both clearances are tracked in database records (`applications.status`, `health_records.status`).

### Phase 3: Academic Enrollment & Assessment
- **Actors:** Applicant, Cashiers (`cashier`), Scholarship Officers (`scholarship`).
- **Key Actions:** Applicant selects timetable sections from available curriculum offerings; system calculates tuition dynamically using enrolled units $\times$ rate per unit; optional scholarship discounts are applied; applicant uploads bank payment proof; cashier verifies payment and issues official receipt.

### Phase 4: Final Admission & LMS Provisioning
- **Actors:** Admissions, Background Mailer Service.
- **Key Actions:** Admissions marks enrollment as finalized; system generates official Student Number (`YYYY-XXXXXX`), sets up institutional TTU email, dispatches welcome credentials email, and auto-provisions active course records in `lms_courses`.

### Phase 5: LMS Academic Delivery
- **Actors:** Enrolled Student (`applicant` / `student`), Faculty Instructors (`faculty`).
- **Key Actions:** Student logs into LMS via Student Number; accesses lecture modules; participates in timed quizzes; uploads assignment submissions; views real-time attendance and gradebook records.

---
**Related:**
- [[Applicant Registration Workflow]]
- [[Health Submission & Clearance Workflow]]
- [[Payment & Assessment Workflow]]
- [[LMS]]
- [[Module Index]]
