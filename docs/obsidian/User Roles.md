# User Roles & RBAC

The system employs a strict Role-Based Access Control (RBAC) mechanism governed by the `users.role` column. Every backend script verifies access via `includes/auth.php` using the `requireRole()` or `requireAnyRole()` functions.

## Internal Staff Roles

### 1. Superadmin (`superadmin`)
- Has unrestricted access to all modules, settings, and logs.
- Can create, edit, and delete other staff accounts.
- Acts as the fallback for any departmental overrides.
- *Dashboard:* `admin/dashboard.php`

### 2. Admissions Officer (`admissions`)
- Responsible for the first line of applicant screening.
- Verifies uploaded documents (birth certificates, report cards).
- Approves or rejects incoming applications.
- *Dashboard:* `admin/admissions/admissions_dashboard.php`
- *Related:* [[Modules/Admissions]]

### 3. Registrar (`registrar`)
- The academic core of the system.
- Manages curricula, subjects, programs, and sections.
- Generates section schedules to prevent conflicts.
- Performs the final "Enroll" action after all fees are settled.
- *Dashboard:* `admin/registrar/registrar_dashboard.php`
- *Related:* [[Modules/Registrar]]

### 4. Cashier / Finance (`cashier`)
- Manages university income and fee structures.
- Generates Assessments (bills) based on a student's program and scholarships.
- Records over-the-counter payments and issues digital receipts.
- *Dashboard:* `admin/finance/finance_dashboard.php`
- *Related:* [[Modules/Finance]]

### 5. University Clinic (`clinic`)
- Manages the health clearance requirement.
- Reviews submitted medical forms (X-rays, blood types).
- Grants "Medical Clearance" which is a hard prerequisite for enrollment.
- *Dashboard:* `admin/clinic/clinic_dashboard.php`
- *Related:* [[Modules/Clinic]]

### 6. Scholarship Coordinator (`scholarship`)
- Manages the roster of available university grants.
- Reviews and approves/rejects student applications for financial aid.
- *Dashboard:* `admin/scholarship/scholarship_dashboard.php`
- *Related:* [[Modules/Scholarship]]

---

## External Roles

### Applicant (`applicant`)
- The end-user of the system.
- Creates an account, submits an enrollment application, uploads documents, tracks status, and views their assessment/schedule.
- Cannot access anything inside the `admin/` directory.
- *Dashboard:* `applicant/dashboard.php`
