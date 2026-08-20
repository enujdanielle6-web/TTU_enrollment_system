# Email & Notification System

This document specifies the email delivery subsystem, PHPMailer integration, Google SMTP configuration, email templates, and error handling behaviors across the TTU Enrollment System.

---

## 1. SMTP & Delivery Engine
Email transmission is powered by **PHPMailer (v6.9+)** using Google SMTP TLS:
- **Host:** `smtp.gmail.com`
- **Port:** `587`
- **Encryption:** `STARTTLS` (`PHPMailer::ENCRYPTION_STARTTLS`)
- **Authentication:** Google App Password (16-character token)

---

## 2. Environment Configuration (`.env`)
```ini
SMTP_HOST="smtp.gmail.com"
SMTP_PORT="587"
SMTP_ENCRYPTION="tls"
SMTP_USERNAME="ttu.enrollment@gmail.com"
SMTP_PASSWORD="your-google-app-password"
MAIL_FROM_ADDRESS="ttu.enrollment@gmail.com"
MAIL_FROM_NAME="Triple T University"
```

> **Automated Environment Loading:**
> Environment variables in `.env` are automatically loaded into `$_ENV`, `$_SERVER`, and `getenv()` at the very top of `public/index.php` and with safety fallbacks inside helper functions.

---

## 3. Official Email Templates

### 3.1 6-Digit Email OTP Verification ([`email_verification.php`](file:///c:/xampp/htdocs/sia/app/Views/emails/email_verification.php))
- **Trigger:** Dispatched during applicant registration or upon requesting a code resend.
- **Visual Design:** Features the **Triple T University campus image** (`cid:ttu_campus`) in the header overlaid with a royal blue gradient tint (`rgba(8, 38, 98, 0.82) -> rgba(13, 71, 161, 0.88)`), the university seal logo (`cid:ttu_logo`), and high-contrast typography.
- **Content:** Displays the active 6-digit verification OTP in a prominent monospace box with 15-minute expiration notice and security reminders.

### 3.2 Student & LMS Credentials ([`welcome_credentials.php`](file:///c:/xampp/htdocs/sia/app/Views/emails/welcome_credentials.php))
- **Trigger:** Dispatched when Admissions finalizes an applicant's enrollment (`applications.status = 'enrolled'`).
- **Content:** Welcome letter presenting the student's assigned **TTU Institutional Email** (`first.last@ttu.edu.ph`), **Student Number** (`YYYY-XXXXXX`), **Temporary Password**, and a direct login button to the Student Portal & LMS.

---

## 4. Helper Dispatch Functions ([`functions.php`](file:///c:/xampp/htdocs/sia/app/Helpers/functions.php))

### `sendVerificationCodeEmail()`
```php
function sendVerificationCodeEmail(
    string $recipientEmail, 
    string $recipientName, 
    string $code, 
    ?string &$errorMessage = null
): bool
```
- Validates recipient email format via `filter_var()`.
- Embeds `ttu_logo` (`images/TTU_LOGO.png`) and `ttu_campus` (`images/ttu_campus.jpg`).
- Returns `true` on successful SMTP dispatch; returns `false` and captures technical error into `$errorMessage` and error log.

### `sendStudentCredentialsEmail()`
```php
function sendStudentCredentialsEmail(
    string $recipientEmail, 
    string $firstName, 
    string $ttuEmail, 
    string $studentNumber, 
    string $tempPassword, 
    ?string &$errorMessage = null
): bool
```
- Dispatches official student credentials with automatic attachment embedding and delivery error capture.

---

## 5. Error & Delivery Feedback Handling
- **Registration Flow:** If SMTP transmission succeeds, displays a green success notice. If delivery fails (e.g. invalid host/app password), account registration is preserved, and a warning banner notifies the applicant to click "Resend Code" or verify their email address.
- **Resend Flow:** Displays clear error messages if SMTP fails so users and administrators can diagnose mail server issues immediately.

---
**Related:**
- [[Authentication & Email Verification]]
- [[Applicant Registration Workflow]]
- [[Admissions]]
- [[Development Guide]]
