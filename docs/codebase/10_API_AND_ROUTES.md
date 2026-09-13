# 10. API AND ROUTES

All application routes are defined in `app/Routes/web.php` (268 lines).

## Route Groups

### 1. Public Routes (No Middleware)
- `GET /sia/`: Landing page (`HomeController::index`)
- `GET /sia/auth/login.php`: Main login form (`AuthController::login`)
- `POST /sia/auth/login.php`: Main login processing (`AuthController::processLogin`)
- `GET /sia/auth/register.php`: Public registration (`AuthController::register`)
- `POST /sia/auth/register.php`: Process registration (`AuthController::processRegister`)
- `GET /sia/auth/lms_student_login.php`: Student LMS login (`LmsAuthController::login`)
- `GET /sia/auth/lms_faculty_login.php`: Faculty LMS login (`LmsAuthController::login`)

### 2. Applicant Route Group
- Middleware: `SessionSecurityMiddleware`, `CsrfMiddleware`, `AuthMiddleware`, `RoleMiddleware:applicant`
- `GET /sia/applicant/dashboard.php`: Applicant portal dashboard
- `GET /sia/applicant/documents.php`: Uploaded documents overview
- `POST /sia/applicant/documents.php`: Requirement document upload
- `GET /sia/applicant/health_record.php`: Medical questionnaire
- `POST /sia/applicant/health_record.php`: Medical survey submission

### 3. Admin Route Group
- Middleware: `SessionSecurityMiddleware`, `CsrfMiddleware`, `AuthMiddleware`, `RoleMiddleware:admin`
- `GET /sia/admin/dashboard.php`: Admin overview
- `GET /sia/admin/admissions/review.php`: Applications review queue
- `POST /sia/admin/admissions/process.php`: Application approval/rejection
- `GET /sia/admin/clinic/medical_clearance.php`: Clinic medical verification
- `GET /sia/admin/finance/cashier_dashboard.php`: Finance dashboard
- `POST /sia/admin/finance/process.php`: Payment processing
- `GET /sia/admin/registrar/subjects.php`: Subject catalog
- `POST /sia/admin/registrar/subjects.php`: Subject create/update
- `GET /sia/admin/scheduler/scheduler_dashboard.php`: Timetable manager
- `GET /sia/admin/system/users.php`: User management (Superadmin only)

### 4. LMS Route Group
- Middleware: `SessionSecurityMiddleware`, `CsrfMiddleware`, `AuthMiddleware` *(Lacks role middleware)*
- Student endpoints: `/sia/lms/student/*` (dashboard, course, assignments, quizzes, gradebook, attendance)
- Faculty endpoints: `/sia/lms/faculty/*` (dashboard, course, module_create, material_upload, assignments, quizzes, gradebook, attendance)
- Download streaming: `/sia/lms/download/material/{id}`, `/sia/lms/download/submission/{id}`
