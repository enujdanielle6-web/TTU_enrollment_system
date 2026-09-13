# 09. MODULE & FILE RELATIONSHIPS

## 1. Admissions Subsystem
```text
Admissions Module
├── Route: /sia/admin/admissions/* (app/Routes/web.php)
├── Controller: app/Controllers/Admin/Admissions/AdmissionsController.php
│    ├── Calls: app/Services/EnrollmentService.php
│    │    ├── Calls: app/Services/StudentNumberService.php
│    │    └── Uses: Vendor PHPMailer
│    ├── Queries: MariaDB (applications, users, application_documents)
│    └── Models: app/Models/Application.php, app/Models/ApplicationDocument.php
├── Views: app/Views/admin/admissions/
│    ├── review.php (Queue review)
│    └── detail.php (Applicant record breakdown)
└── Supporting Controllers: app/Controllers/DocumentController.php
```

## 2. Finance Subsystem
```text
Finance & Cashier Module
├── Route: /sia/admin/finance/* (app/Routes/web.php)
├── Controllers:
│    ├── app/Controllers/Admin/Finance/FinanceController.php
│    └── app/Controllers/Admin/Finance/FeeController.php
│         ├── Calls: app/Services/AssessmentService.php
│         └── Queries: MariaDB (fee_templates, student_assessments, payment_records)
├── Views: app/Views/admin/finance/
│    ├── cashier_dashboard.php
│    ├── cashier_payments.php (Contains direct PDO query finding)
│    ├── assessment.php
│    └── fees.php
```

## 3. LMS Subsystem
```text
LMS Module
├── Routes: /sia/lms/* (app/Routes/web.php)
├── Controllers:
│    ├── app/Controllers/Lms/StudentController.php
│    ├── app/Controllers/Lms/FacultyController.php
│    ├── app/Controllers/Lms/DownloadController.php
│    ├── app/Controllers/Lms/StudentQuizController.php / FacultyQuizController.php
│    ├── app/Controllers/Lms/StudentAssignmentController.php / FacultyAssignmentController.php
│    ├── app/Controllers/Lms/StudentGradebookController.php / FacultyGradebookController.php
│    └── app/Controllers/Lms/StudentAttendanceController.php / FacultyAttendanceController.php
├── Services:
│    ├── app/Services/LmsService.php
│    ├── app/Services/LmsQuizService.php
│    ├── app/Services/LmsGradebookService.php
│    └── app/Services/LmsCalendarService.php
├── Repositories:
│    ├── app/Repositories/CollegeEnrollmentRepository.php
│    └── app/Repositories/ShsEnrollmentRepository.php
└── Views: app/Views/lms/
     ├── student/ (dashboard, course, assignments, quizzes, gradebook, attendance)
     └── faculty/ (dashboard, course, assignments, quizzes, gradebook, attendance)
```
