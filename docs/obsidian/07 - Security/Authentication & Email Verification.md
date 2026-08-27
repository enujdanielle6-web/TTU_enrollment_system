# Authentication & Email Verification

This document specifies the end-to-end authentication lifecycle, 6-digit OTP email verification gating, and multi-portal role-based access control.

---

## 1. Authentication Lifecycle

```mermaid
flowchart TD
    Reg[1. Applicant Registers on /auth/register.php] --> CreateAcc[2. Account Created: email_verified = 0]
    CreateAcc --> GenOTP[3. Generate 6-Digit Random OTP & 15m Expiry]
    GenOTP --> SendMail[4. sendVerificationCodeEmail: Dispatches Branded HTML Email]
    SendMail --> RedirectOTP[5. Redirects to /auth/verify_email.php with Pending Session]
    RedirectOTP --> UserInputs[6. User Enters 6-Digit Code]
    UserInputs --> VerifyCheck{7. Code Matches & Not Expired?}
    VerifyCheck -->|No| Err[Display Error / Offer 60s Resend]
    Err --> UserInputs
    VerifyCheck -->|Yes| SetVerified[8. UPDATE users: email_verified=1, code=NULL]
    SetVerified --> AuthSession[9. Authenticate Session: logged_in=true]
    AuthSession --> Dash[10. Redirect to /applicant/dashboard.php]
```

---

## 2. Technical Implementation Details

### Database Schema Updates (`users`)
- `email_verified` (TINYINT(1), DEFAULT 0): Gating flag. 0 = Unverified (cannot log in), 1 = Verified.
- `verification_code` (VARCHAR(10), NULL): 6-digit random string (e.g., `481920`).
- `verification_code_expires_at` (DATETIME, NULL): Expiration timestamp (15 minutes).

```sql
-- Registration insert
INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, verification_code, verification_code_expires_at) 
VALUES (?, ?, ?, ?, 'applicant', 1, 0, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE));
```

### Verification Interface (`/auth/verify_email.php`)
- **Interactive OTP Input:** 6 individual digit boxes with auto-advance, backspace navigation, and clipboard paste distribution.
- **Resend Cooldown:** 60-second JavaScript timer and backend cooldown check.
- **Change Email Option:** Allows the user to restart registration if an incorrect email was provided.

### Login Interception Gating
In `AuthController::login`:
- If an applicant authenticates with valid password but `email_verified === 0`:
  1. Login is blocked.
  2. A fresh 6-digit OTP is generated.
  3. A new verification email is dispatched.
  4. The applicant is redirected to `/auth/verify_email.php`.

---

## 3. Multi-Portal Authentication Endpoints

| Portal | Login Route | Auth Controller & Action | Allowed Credentials | Post-Login Landing |
|---|---|---|---|---|
| **Admissions & Admin** | `/auth/login.php` | `AuthController@login` | Email & Password | `/admin/dashboard.php` |
| **Applicant Portal** | `/auth/login.php` | `AuthController@login` | Email & Password (must be verified) | `/applicant/dashboard.php` |
| **Student LMS** | `/auth/lms_student_login.php` | `LmsAuthController@loginProcess` | **Student Number** (`YYYY-XXXXXX`) & Unified Password | `/lms/student/dashboard.php` |
| **Faculty LMS** | `/auth/lms_faculty_login.php` | `LmsAuthController@loginProcess` | **Faculty Employee ID** & Unified Password | `/lms/faculty/dashboard.php` |

---

## 4. Dedicated Sign-out Endpoints
- **Student LMS Logout:** `/auth/lms_student_logout.php` (or `/lms/student/logout`) $\rightarrow$ redirects to `/auth/lms_student_login.php`.
- **Faculty LMS Logout:** `/auth/lms_faculty_logout.php` (or `/lms/faculty/logout`) $\rightarrow$ redirects to `/auth/lms_faculty_login.php`.
## 5. Password Recovery & 6-Digit Reset OTP
- **Applicant Reset:** `/auth/forgot_password.php?portal=applicant` $\rightarrow$ prompts for registered email $\rightarrow$ sends 6-digit OTP code to applicant email $\rightarrow$ redirects to `/auth/reset_password.php?portal=applicant`.
- **Faculty LMS Reset:** `/auth/forgot_password.php?portal=faculty` $\rightarrow$ prompts for **Employee ID** or **TTU Email** $\rightarrow$ verifies faculty role $\rightarrow$ dispatches 6-digit OTP code to official institutional TTU email address (`faculty@ttu.edu.ph`) $\rightarrow$ redirects to `/auth/reset_password.php?portal=faculty`.
- **Student LMS Reset:** `/auth/forgot_password.php?portal=student` $\rightarrow$ prompts for **Student ID** (e.g. `2026-000002`) or **TTU Email** $\rightarrow$ dispatches 6-digit OTP code to student's institutional/registered email address $\rightarrow$ redirects to `/auth/reset_password.php?portal=student`.
- **Reset Processing:** `/auth/reset_password_process.php` verifies the 6-digit OTP matches `users.reset_password_code` within the 15-minute window (`reset_password_expires_at > NOW()`), updates `users.password` with `password_hash()`, clears reset code, and redirects back to the respective portal with success banner.

---
**Related:**
- [[Email & Notification System]]
- [[Applicant Registration Workflow]]
- [[Security Overview]]
- [[Users Table]]

