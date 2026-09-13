# 08. WORKFLOWS

## 1. Applicant Registration & OTP Verification
```text
POST /sia/auth/register.php
 └── AuthController::register()
      ├── Validate fields & password strength
      ├── INSERT INTO users (role='applicant', email_verified=0)
      └── Generate 6-digit OTP -> Session & users.verification_code
           └── POST /sia/auth/verify_otp.php -> AuthController::verifyOtp()
                └── UPDATE users SET email_verified=1 -> Login Session Active
```

## 2. Application Intake & Requirement Upload
```text
POST /sia/enroll/college.php or /shs.php
 └── EnrollController::submit()
      └── INSERT INTO applications (status='pending', ref='APP-YYYY-XXXXXX')
           └── POST /sia/applicant/documents.php
                └── DocumentController::upload()
                     └── finfo MIME validation -> Move to app/uploads/documents/
                          └── INSERT INTO application_documents (status='pending')
```

## 3. Admissions Committee Review & Clearance
```text
GET /sia/admin/admissions/review.php -> AdmissionsController::review()
 └── Inspect documents, medical status, academic history
      └── POST /sia/admin/admissions/process.php
           └── UPDATE applications SET status='approved'
```

## 4. Assessment & Cashier Payment
```text
GET /sia/admin/finance/assessment.php?id={app_id}
 └── AssessmentService::generateAssessment() -> INSERT student_assessments (payment_status='unpaid')
      └── POST /sia/admin/finance/process.php -> FinanceController::process()
           └── Transaction: UPDATE student_assessments FOR UPDATE
                ├── INSERT INTO payment_records
                ├── UPDATE student_assessments (total_paid += amount, payment_status='paid')
                └── UPDATE applications SET status='payment_verified'
```

## 5. Enrollment Finalization
```text
POST /sia/admin/registrar/finalize.php -> RegistrarController::finalize()
 └── EnrollmentService::finalizeEnrollment()
      ├── Verify payment_status in ('partial', 'paid')
      ├── StudentNumberService::generate() -> users.student_number
      ├── Generate institutional ttu_email (first.last@ttu.edu.ph)
      ├── UPDATE users SET role='student', force_password_reset=1
      ├── INSERT INTO college_enrollments / shs_enrollments
      ├── UPDATE applications SET status='enrolled'
      └── Send welcome credentials email via PHPMailer
```

## 6. LMS Student Access & Course Participation
```text
GET /sia/lms/student/dashboard.php -> StudentController::dashboard()
 └── LmsService::getStudentCourses()
      └── CollegeEnrollmentRepository::getActiveStudentCourses()
           ├── Reads enrolled section subjects
           └── Renders Course Cards
                ├── Materials: GET /sia/lms/download/material/{id}
                ├── Assignments: POST /sia/lms/student/.../assignments/{id}/submit
                └── Quizzes: POST /sia/lms/student/.../quizzes/{id}/submit
```
