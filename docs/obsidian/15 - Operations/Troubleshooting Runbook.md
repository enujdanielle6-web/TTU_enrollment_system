# Troubleshooting Runbook

This runbook catalogs known runtime symptoms, diagnostic procedures, and verified resolutions for administrators and developers operating the TTU Enrollment System and LMS.

---

## 1. Common Runtime Issues & Resolutions

### 1.1 Email Verification OTP Not Received
* **Symptom:** Yellow warning banner on `/auth/verify_email.php` indicating email delivery issue.
* **Diagnostics:**
  1. Inspect Apache error log: `C:\xampp\apache\logs\error.log`.
  2. Verify that `.env` exists in the project root.
  3. Verify `SMTP_USERNAME` and `SMTP_PASSWORD` match your Google Account App Password.
* **Resolution:** Ensure a 16-character Google App Password (not your personal account password) is used, and click "Resend Code".

### 1.2 "404 Not Found" on Sub-pages
* **Symptom:** Navigating to `/applicant/dashboard.php` or `/admin/dashboard.php` returns an Apache 404 error.
* **Diagnostics:** Apache `mod_rewrite` is disabled or `.htaccess` overrides are blocked.
* **Resolution:** Open Apache `httpd.conf`, ensure `LoadModule rewrite_module` is uncommented, and set `AllowOverride All` for the webroot directory.

### 1.3 Screen Blacks Out When Opening Modals
* **Symptom:** Clicking "Edit" or "View" causes the screen to dim black and freeze.
* **Root Cause:** Bootstrap 5 modal markup is nested inside a `<tbody>` tag.
* **Resolution:** Move modal HTML markup outside the `<table>` to the bottom of the view template.

### 1.4 Database Schema Mismatch
* **Symptom:** SQL error indicating missing column (e.g. `is_per_unit` in `fee_templates` or `verification_code` in `users`).
* **Resolution:** Import the latest [`schema_dump.sql`](file:///c:/xampp/htdocs/sia/schema_dump.sql) into MariaDB to align with current production schema.

---

## 2. Password Reset & Account Recovery

If an admin account is locked or password is forgotten:
1. Generate a Bcrypt hash in PHP CLI:
   ```bash
   php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
   ```
2. Execute SQL update in MariaDB:
   ```sql
   UPDATE users SET password = '<generated_hash>', is_active = 1 WHERE email = 'admin@ttu.edu.ph';
   ```

---
**Related:**
- [[Installation & Setup Guide]]
- [[Development Guide]]
- [[Known Issues]]
