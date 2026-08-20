# Local Development Guide

This guide provides step-by-step instructions for configuring, running, debugging, and maintaining the TTU Enrollment System on local environments.

---

## 1. System Requirements
- **Web Server:** Apache 2.4+ (XAMPP for Windows / Linux / macOS)
- **PHP Version:** PHP 7.4 or 8.x (PHP 8.2+ recommended)
  - Required Extensions: `pdo_mysql`, `openssl`, `mbstring`, `curl`, `json`, `fileinfo`
- **Database:** MariaDB 10.4+ or MySQL 8.0+
- **Dependency Manager:** Composer 2.x

---

## 2. Quickstart Installation

### Step 1: Clone or Place in Webroot
Place the project repository inside your web server's document root (e.g. `C:\xampp\htdocs\sia`).

### Step 2: Database Initialization
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI.
3. Create a database named `sia`:
   ```sql
   CREATE DATABASE sia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Import the official 41-table schema:
   ```bash
   mysql -u root -p sia < schema_dump.sql
   ```

### Step 3: Install PHP Dependencies
From the project root:
```bash
composer install
```
*(Installs PHPMailer and initializes the PSR-4 vendor autoloader).*

### Step 4: Environment Configuration (`.env`)
Create a `.env` file in the root directory:
```ini
SMTP_HOST="smtp.gmail.com"
SMTP_PORT="587"
SMTP_ENCRYPTION="tls"
SMTP_USERNAME="your-email@gmail.com"
SMTP_PASSWORD="your-google-app-password"
MAIL_FROM_ADDRESS="no-reply@ttu.edu.ph"
MAIL_FROM_NAME="Triple T University"

DB_HOST="localhost"
DB_PORT="3306"
DB_DATABASE="sia"
DB_USERNAME="root"
DB_PASSWORD=""

APP_ENV="development"
```

> **Important for Email OTP & Credentials:**
> Google requires a **16-character App Password** (not your regular Gmail password). Generate this in your Google Account under **Security > 2-Step Verification > App Passwords**.

---

## 3. Standard Login Credentials for Testing

| Role | Username / Email / ID | Password | Access Portal |
|---|---|---|---|
| **Superadmin** | `admin@ttu.edu.ph` | `admin123` | `/admin/dashboard.php` |
| **Admissions** | `admissions@ttu.edu.ph` | `admin123` | `/admin/admissions/admissions_dashboard.php` |
| **Registrar** | `registrar@ttu.edu.ph` | `admin123` | `/admin/registrar/registrar_dashboard.php` |
| **Cashier / Finance** | `cashier@ttu.edu.ph` | `admin123` | `/admin/finance/cashier_dashboard.php` |
| **Clinic Officer** | `clinic@ttu.edu.ph` | `admin123` | `/admin/clinic/clinic_dashboard.php` |
| **Scheduler** | `scheduler@ttu.edu.ph` | `admin123` | `/admin/scheduler/scheduler_dashboard.php` |
| **Scholarship** | `scholarship@ttu.edu.ph` | `admin123` | `/admin/scholarship/scholarship_dashboard.php` |
| **Enrolled Student** | `2026-000003` (or applicant email) | `password123` | `/lms/student/dashboard.php` / `/applicant/` |
| **Faculty Instructor** | Faculty Employee ID | `password123` | `/lms/faculty/dashboard.php` |

---

## 4. Debugging & Verification
- **PHP Syntax Linting:**
  ```bash
  php -l app/Controllers/AuthController.php
  ```
- **Error Logs:** Check Apache PHP error log (`C:\xampp\apache\logs\error.log`).
- **Database Logs:** Check `activity_logs` table for administrative audit trails.

---
**Related:**
- [[System Architecture]]
- [[Project Structure & Code Map]]
- [[AI Development Context]]
- [[Troubleshooting Runbook]]
