# Applicant Portal Relationship Map

This document traces the complete page-to-code chains, interactive AJAX endpoints, database flows, and form lifecycles for the Applicant Self-Service Portal.

---

## 1. Applicant Dashboard (`/applicant/dashboard.php`)

### Page Identity
- **File Path:** [`app/Views/applicant/dashboard.php`](file:///c:/xampp/htdocs/sia/app/Views/applicant/dashboard.php)
- **Controller:** [`app/Controllers/ApplicantController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php) (`dashboard()`)
- **Route:** `GET /applicant/dashboard.php`
- **Authorized Role:** `applicant`
- **Middleware:** `SessionSecurityMiddleware`, `AuthMiddleware`, `RoleMiddleware:applicant`

### Tracing Chain
```text
GET /applicant/dashboard.php
    ↓
ApplicantController@dashboard
    ↓
1. PDO SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1
2. PDO SELECT * FROM application_documents WHERE application_id = ?
3. PDO SELECT * FROM health_records WHERE application_id = ? LIMIT 1
4. PDO SELECT * FROM student_assessments WHERE application_id = ? LIMIT 1
5. PDO SELECT * FROM announcements WHERE is_active = 1
    ↓
Renders: app/Views/applicant/dashboard.php with Step Progress Bar (1 to 5)
```

---

## 2. Application Form (`/applicant/application_form.php`)

### Page Identity
- **File Path:** [`app/Views/applicant/application_form.php`](file:///c:/xampp/htdocs/sia/app/Views/applicant/application_form.php)
- **Controller:** [`app/Controllers/ApplicantController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php) (`form()`, `saveForm()`)
- **Routes:** `GET /applicant/application_form.php`, `POST /applicant/application_form.php`

### Tracing Chain & Data Flow
```text
POST /applicant/application_form.php (academic_level, strand/program, grade_level, demographics, LRN, address, guardian)
    ↓
ApplicantController@saveForm
    ↓
Validation:
    ├── validateLRN($lrn) -> 12 numeric digits
    ├── validatePHPhone($contact) -> 11 digits starting with 09
    └── Required fields check
    ↓
Generate Unique Ref Number: generateReferenceNumber() -> e.g. "APP-2026-0012"
    ↓
Database Operation:
    ├── [IF NEW]: PDO INSERT INTO applications (user_id, reference_number, academic_level, grade_level, school_year, semester, strand, status, ...)
    └── [IF EDIT]: PDO UPDATE applications SET ... WHERE id = ?
    ↓
logActivity($userId, 'Application Submitted', 'Submitted application form for ' . $academicLevel)
    ↓
Redirect: /applicant/documents.php
```

---

## 3. Digital Requirements Upload (`/applicant/documents.php`)

### Page Identity
- **File Path:** [`app/Views/applicant/documents.php`](file:///c:/xampp/htdocs/sia/app/Views/applicant/documents.php)
- **Controller:** [`app/Controllers/DocumentController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/DocumentController.php) (`index()`, `upload()`)
- **Routes:** `GET /applicant/documents.php`, `POST /applicant/documents_upload.php`

### Tracing Chain & File Storage
```text
POST /applicant/documents_upload.php (multipart/form-data: document_name, document_file)
    ↓
DocumentController@upload
    ↓
Validation:
    ├── MIME check: PDF, JPG, JPEG, PNG only
    └── File size <= 5MB
    ↓
File Storage: Move uploaded file to `app/uploads/documents/{unique_timestamp}_{filename}`
    ↓
Database Operation:
    ├── PDO INSERT/UPDATE INTO application_documents (application_id, document_name, file_path, status)
    │   VALUES (?, ?, ?, 'pending')
    └── Update applications SET status = 'pending'
    ↓
Redirect: /applicant/documents.php with success notification
```

---

## 4. Health Information Submission (`/applicant/health_info.php`)

### Page Identity
- **File Path:** [`app/Views/applicant/health_info.php`](file:///c:/xampp/htdocs/sia/app/Views/applicant/health_info.php)
- **Controller:** [`app/Controllers/HealthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/HealthController.php) (`index()`, `save()`)
- **Routes:** `GET /applicant/health_info.php`, `POST /applicant/health_info.php`

### Tracing Chain
```text
POST /applicant/health_info.php (blood_type, height, weight, allergies, medical_conditions, emergency_name, emergency_contact)
    ↓
HealthController@save
    ↓
Validation: Physical measurements & emergency contact phone format
    ↓
PDO INSERT / UPDATE INTO health_records
    (user_id, application_id, blood_type, height, weight, medical_conditions, emergency_name, emergency_contact, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ↓
Redirect: /applicant/dashboard.php
```

---

## 5. Subject Enrollment & Schedule Selector (`/applicant/enroll.php`)

### Page Identity
- **File Path:** [`app/Views/applicant/enroll.php`](file:///c:/xampp/htdocs/sia/app/Views/applicant/enroll.php)
- **Controller:** [`app/Controllers/EnrollController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/EnrollController.php) (`index()`, `enroll()`)
- **Routes:** `GET /applicant/enroll.php`, `POST /applicant/enroll.php`

### Interactive AJAX Endpoints Traced
During schedule selection on `enroll.php`, client-side JavaScript calls the following AJAX endpoints:
1. `GET /applicant/api_get_curriculum.php?program_id={id}` $\rightarrow$ `ApplicantApiController@getCurriculum`
2. `GET /applicant/api_get_sections.php?curriculum_id={id}&year_level={year}` $\rightarrow$ `ApplicantApiController@getSections`
3. `GET /applicant/api_get_section_subjects.php?section_id={id}` $\rightarrow$ `ApplicantApiController@getSectionSubjects`
4. `GET /applicant/api_get_schedule.php?application_id={id}` $\rightarrow$ `ApplicantApiController@getSchedule`

### Enrollment Submission Chain
```text
POST /applicant/enroll.php (section_id, selected_subjects[])
    ↓
EnrollController@enroll
    ↓
Validation:
    ├── Section capacity check: capacity > current_enrolled_count
    └── Schedule conflict check via Schedule::validateSelectedSubjects()
    ↓
Database Operations (Wrapped in PDO Transaction):
    ├── 1. PDO UPDATE applications SET section_id = ? WHERE id = ?
    ├── 2. PDO DELETE FROM college_enrollments WHERE application_id = ? (Clear prior draft)
    ├── 3. For each subject:
    │      PDO INSERT INTO college_enrollments (application_id, subject_id, college_section_id) VALUES (?, ?, ?)
    └── 4. Calculate total enrolled units & generate draft student_assessments
    ↓
Redirect: /applicant/assessment.php
```

---

## 6. Assessment Slip & Bank Payment Proof (`/applicant/assessment.php`)

### Page Identity
- **File Path:** [`app/Views/applicant/assessment.php`](file:///c:/xampp/htdocs/sia/app/Views/applicant/assessment.php)
- **Controller:** [`app/Controllers/ApplicantController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/ApplicantController.php) (`assessment()`, `uploadPaymentProof()`)
- **Routes:** `GET /applicant/assessment.php`, `POST /applicant/payment_proof_upload.php`

### Tracing Chain
```text
POST /applicant/payment_proof_upload.php (payment_method, reference_number, proof_image)
    ↓
ApplicantController@uploadPaymentProof
    ↓
Store proof image in app/uploads/payments/
    ↓
PDO INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, reference_number, proof_image, status)
    VALUES (?, ?, ?, NOW(), ?, ?, ?, 'pending')
    ↓
Update student_assessments SET payment_status = 'partial' (if previous 'unpaid')
    ↓
Redirect: /applicant/assessment.php with modal confirmation
```

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[05 - Admissions Admin Relationship Map]]
- [[09 - Finance & Cashier Relationship Map]]
