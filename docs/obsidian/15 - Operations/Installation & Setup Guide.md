# Installation & Setup Guide

This runbook provides complete instructions for deploying and running the TTU Enrollment System and LMS on a fresh server or development machine.

---

## 1. Prerequisites & Environment
Ensure the host machine has:
- **XAMPP** (or standalone Apache 2.4 + MariaDB 10.4+ / MySQL 8.0)
- **PHP 7.4+ or 8.x** (PHP 8.2 recommended) with extensions:
  - `pdo_mysql`, `openssl`, `mbstring`, `curl`, `json`, `fileinfo`
- **Composer** (v2.x)
- **Git**

---

## 2. Step-by-Step Installation

### Step 1: Clone Repository into Webroot
```bash
cd /xampp/htdocs
git clone <repository_url> sia
cd sia
```

### Step 2: Configure Web Server (`.htaccess` & Rewrite)
Ensure Apache has `mod_rewrite` enabled:
1. Open `httpd.conf` in Apache configuration.
2. Ensure `LoadModule rewrite_module modules/mod_rewrite.so` is uncommented.
3. Ensure directory overrides are allowed:
   ```apache
   <Directory "C:/xampp/htdocs">
       AllowOverride All
       Require all granted
   </Directory>
   ```
4. Restart Apache.

### Step 3: Database Setup
1. Start MySQL in XAMPP Control Panel.
2. Log into MySQL CLI or open phpMyAdmin:
   ```sql
   CREATE DATABASE sia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the complete 41-table schema:
   ```bash
   mysql -u root -p sia < schema_dump.sql
   ```

### Step 4: Install PHP Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### Step 5: Environment File Configuration (`.env`)
Copy `.env.example` or create `.env` in the root:
```ini
SMTP_HOST="smtp.gmail.com"
SMTP_PORT="587"
SMTP_ENCRYPTION="tls"
SMTP_USERNAME="ttu.enrollment@gmail.com"
SMTP_PASSWORD="your-16-char-google-app-password"
MAIL_FROM_ADDRESS="ttu.enrollment@gmail.com"
MAIL_FROM_NAME="Triple T University"

DB_HOST="localhost"
DB_PORT="3306"
DB_DATABASE="sia"
DB_USERNAME="root"
DB_PASSWORD=""

APP_ENV="development"
```

> **Google App Password Instructions:**
> 1. Log into your Google Account.
> 2. Navigate to **Security > 2-Step Verification**.
> 3. Scroll to the bottom and select **App Passwords**.
> 4. Create an App Password with name `TTU Enrollment System`.
> 5. Copy the generated 16-character code into `SMTP_PASSWORD` in `.env`.

---

## 3. Verifying the Installation
1. Open your browser to `http://localhost/sia/public/index.php` (or `http://localhost/sia/`).
2. Test applicant registration at `http://localhost/sia/auth/register.php`.
3. Check that the 6-digit OTP email is delivered and verification unlocks the dashboard.
4. Test administrative login with `admin@ttu.edu.ph` / `admin123`.

---
**Related:**
- [[Development Guide]]
- [[Troubleshooting Runbook]]
- [[System Architecture]]
