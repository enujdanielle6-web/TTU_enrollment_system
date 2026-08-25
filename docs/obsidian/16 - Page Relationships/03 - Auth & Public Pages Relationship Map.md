# Auth & Public Pages Relationship Map

This document traces the complete code relationships, data flows, and dependencies for all authentication and public-facing pages.

---

## 1. Landing Page (`/`)

### Page Identity
- **File Path:** [`app/Views/home.php`](file:///c:/xampp/htdocs/sia/app/Views/home.php)
- **Controller:** [`app/Controllers/HomeController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/HomeController.php) (`index()`)
- **Route:** `GET /`
- **Access / Role:** Public (Unauthenticated)
- **Middleware:** `SessionSecurityMiddleware`

### Dependencies
- **Views & Components:** [`app/Views/layout_header.php`](file:///c:/xampp/htdocs/sia/app/Views/layout_header.php), [`app/Views/layout_footer.php`](file:///c:/xampp/htdocs/sia/app/Views/layout_footer.php).
- **Assets:** `css/style.css`, Bootstrap 5.3 CSS/JS, Bootstrap Icons.
- **Helpers:** `app/Helpers/functions.php`.

### Tracing Chain
```text
GET /
    ↓
app/Core/Router.php
    ↓
app/Middleware/SessionSecurityMiddleware.php
    ↓
HomeController@index
    ↓
PDO SELECT FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5
    ↓
app/Views/home.php
    ↓
Client Browser
```

### Outgoing Connections
- "Apply Now" Button $\rightarrow$ `/auth/register.php`
- "Portal Login" Button $\rightarrow$ `/auth/login.php`
- "LMS Student Login" $\rightarrow$ `/auth/lms_student_login.php`
- "LMS Faculty Login" $\rightarrow$ `/auth/lms_faculty_login.php`

---

## 2. Universal Login Page (`/auth/login.php`)

### Page Identity
- **File Path:** [`app/Views/auth/login.php`](file:///c:/xampp/htdocs/sia/app/Views/auth/login.php)
- **Controller:** [`app/Controllers/AuthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/AuthController.php) (`login()`)
- **Routes:** `GET /auth/login.php`, `POST /auth/login.php`
- **Access / Role:** Public / Guest
- **Middleware:** `SessionSecurityMiddleware`, `CsrfMiddleware` (on POST)

### Direct Dependencies
- `app/Helpers/functions.php`: `getDbConnection()`, `sanitizeInput()`, `auth_user()`.
- Assets: `css/auth.css`, Bootstrap 5.3.

### Tracing Chain
```text
POST /auth/login.php (email, password, _csrf_token)
    ↓
app/Core/Router.php
    ↓
app/Middleware/CsrfMiddleware.php
    ↓
AuthController@login
    ↓
PDO SELECT FROM login_attempts WHERE ip_address = ? AND attempted_at > NOW() - INTERVAL 15 MINUTE
    ↓
PDO SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1
    ↓
password_verify($password, $user['password'])
    ↓
Check: if ($user['email_verified'] == 0)
    ├── [UNVERIFIED]: Generate OTP, sendVerificationCodeEmail(), Redirect -> /auth/verify_email.php
    └── [VERIFIED]: Set $_SESSION['user_id'], $_SESSION['user_role']
            ↓
Role Redirection Dispatch:
    ├── superadmin / admin -> /admin/dashboard.php
    ├── admissions -> /admin/admissions/admissions_dashboard.php
    ├── clinic -> /admin/clinic/clinic_dashboard.php
    ├── cashier -> /admin/finance/cashier_dashboard.php
    ├── scheduler -> /admin/scheduler/scheduler_dashboard.php
    ├── scholarship -> /admin/scholarship/scholarship_dashboard.php
    ├── faculty -> /lms/faculty/dashboard.php
    └── applicant / student -> /applicant/dashboard.php
```

### Form & Validation
- **Form:** `<form method="POST" action="/auth/login.php">`
- **Validation:** Email format (`filter_var`), non-empty password, max 5 failed attempts per 15 minutes (`login_attempts` table).

---

## 3. Applicant Registration Page (`/auth/register.php`)

### Page Identity
- **File Path:** [`app/Views/auth/register.php`](file:///c:/xampp/htdocs/sia/app/Views/auth/register.php)
- **Controller:** [`app/Controllers/AuthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/AuthController.php) (`register()`)
- **Routes:** `GET /auth/register.php`, `POST /auth/register.php`
- **Access:** Public

### Tracing Chain
```text
POST /auth/register.php (first_name, last_name, email, password, password_confirmation)
    ↓
AuthController@register
    ↓
Validation:
    ├── isPasswordStrong($password) -> >=8 chars, uppercase, lowercase, number, symbol
    └── PDO SELECT id FROM users WHERE email = ? -> Must be unique
    ↓
Hash: password_hash($password, PASSWORD_DEFAULT)
    ↓
OTP Generation: random_int(100000, 999999), verification_code_expires_at = NOW() + 15 MIN
    ↓
PDO INSERT INTO users (first_name, last_name, email, password, role, email_verified, verification_code, verification_code_expires_at)
    VALUES (?, ?, ?, ?, 'applicant', 0, ?, ?)
    ↓
sendVerificationCodeEmail($email, $first_name, $otp) via PHPMailer SMTP
    ↓
Set $_SESSION['pending_verification_user_id'] = $newUserId
    ↓
Redirect: /auth/verify_email.php
```

---

## 4. Email OTP Verification Page (`/auth/verify_email.php`)

### Page Identity
- **File Path:** [`app/Views/auth/verify_email.php`](file:///c:/xampp/htdocs/sia/app/Views/auth/verify_email.php)
- **Controller:** [`app/Controllers/AuthController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/AuthController.php) (`verifyEmail()`, `resendOtp()`)
- **Routes:** `GET /auth/verify_email.php`, `POST /auth/verify_email.php`, `POST /auth/resend_otp.php`

### Frontend & Tracing Chain
```text
User enters 6-digit code in 6 auto-tabbing boxes
    ↓
POST /auth/verify_email.php (code)
    ↓
AuthController@verifyEmail
    ↓
PDO SELECT * FROM users WHERE id = $_SESSION['pending_verification_user_id']
    ↓
Validation:
    ├── Code matches users.verification_code
    └── users.verification_code_expires_at >= NOW()
    ↓
PDO UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires_at = NULL WHERE id = ?
    ↓
Set $_SESSION['user_id'] = $user['id'], $_SESSION['user_role'] = 'applicant'
    ↓
Redirect: /applicant/dashboard.php
```

---

## 5. LMS Student & Faculty Login Pages

- **Student Login:** [`app/Views/lms/student_login.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/student_login.php)
  - **Route:** `GET/POST /auth/lms_student_login.php`
  - **Controller:** `LmsAuthController@studentLogin`
  - **Queries:** `users` where `student_number = ?` OR `email = ?`. Verifies password and checks active `applications` enrollment status.
  - **Redirect:** `/lms/student/dashboard.php`.
- **Faculty Login:** [`app/Views/lms/faculty_login.php`](file:///c:/xampp/htdocs/sia/app/Views/lms/faculty_login.php)
  - **Route:** `GET/POST /auth/lms_faculty_login.php`
  - **Controller:** `LmsAuthController@facultyLogin`
  - **Queries:** `users` where `student_number = ?` (Employee ID) AND `role = 'faculty'`.
  - **Redirect:** `/lms/faculty/dashboard.php`.

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[04 - Applicant Portal Relationship Map]]
- [[07 - Security/Authentication & Email Verification]]
