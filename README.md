# Triple T University (TTU) — Online Enrollment System

Welcome to the **Triple T University (TTU) Online Enrollment System**. This is a comprehensive, production-ready, server-rendered PHP portal designed to streamline student admissions, document submission, health clearances, scholarship applications, financial assessments, and payments.

---

## 🌟 Key Features

* **Applicant Portal**:
  * Multi-stage enrollment form (Personal, Academic, Family, Education, Health, Emergency contact info).
  * Auto-assignment of curriculum subjects based on selected academic programs (for College level).
  * Dual-mode document submissions (online file upload or on-campus physical verification).
  * Dynamic status tracking timeline.
  * Medical information entry & status tracking.
  * Dedicated scholarship search and one-click applications.
  * Dynamic printable Admission Slip generation.
  * Interactive tuition fee assessment dashboard featuring a progress bar, balance calculation, and breakdown.

* **Administrative Portal**:
  * **Super Administrator**: Manage all system users, academic programs, subjects, curriculum mappings, global system settings (e.g., active school year, cost per unit), announcements, view audit logs, and trigger database backups.
  * **Admissions Officer**: Review applications individually or in bulk, verify academic documents, and generate student assessments using customizable fee templates.
  * **Medical Officer/Admissions**: Manage clinic clearance workflows, verify student health declarations, and log clearance remarks.
  * **Scholarship Officer**: Manage scholarship types (fixed or percentage-based) and approve/reject student scholarship applications to automatically apply tuition discounts.
  * **Cashier**: Track assessment balances, record payments (Cash, GCash, Bank Transfer), issue dynamic receipts, and auto-enroll students upon receipt of downpayment or full payment.

---

## 🛠️ Prerequisites & Tech Stack

Before setting up the project, make sure you have the following installed on your system:

1. **XAMPP** (or any bundle providing Apache, PHP 8.x+, and MySQL/MariaDB)
2. **PHP 8.0 or newer** (with PDO extension enabled)
3. **MySQL 5.7+ / MariaDB 10.4+**
4. A modern web browser (Chrome, Firefox, Edge, Safari)

---

## 🚀 Step-by-Step Installation Tutorial

### Step 1: Place Project Files
Clone or copy this project folder into your web server's root folder. For a default XAMPP installation, place it in:
`C:\xampp\htdocs\sia`

Ensure the folder structure matches exactly:
`C:\xampp\htdocs\sia\config\database.php`, `C:\xampp\htdocs\sia\public\index.php`, etc.

### Step 2: Database Configuration
Open [database.php](file:///c:/xampp/htdocs/sia/config/database.php) located in `config/` to configure your database connection parameters if they differ from standard local defaults:

```php
$dbConfig = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'sia',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '', // Set your MySQL password here if not empty
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];
```

### Step 3: Run the Database Setup
The system comes with an automated database setup script (`setup_database.php`) that automatically creates the database structure, imports initial schemas, and inserts development seeds.

Choose **one** of the options below to run it:

#### Option A: Via Web Browser (Recommended)
1. Turn on Apache and MySQL services in the **XAMPP Control Panel**.
2. Open your web browser and navigate to:
   [http://localhost/sia/setup_database.php](http://localhost/sia/setup_database.php)
3. The page will display log messages showing connection, database drop/creation, and imports. 
4. Once completed, you will see a **🎉 Setup Complete!** message.

#### Option B: Via Command Line (CLI)
1. Open your terminal, command prompt, or PowerShell.
2. Navigate to your project directory:
   ```bash
   cd c:\xampp\htdocs\sia
   ```
3. Run the database setup script:
   ```bash
   php setup_database.php
   ```

> [!WARNING]
> **Production Deployment Warning:** For security purposes, delete or rename `setup_database.php` in the project root before launching the application on a public production server to avoid accidental database drops.

---

## 🔑 Default Login Credentials

After setting up the database, you can log in immediately using these pre-seeded accounts:

| Role | Email Address | Password |
|---|---|---|
| **Super Administrator** | `admin@ttu.edu.ph` | `password123` |
| **Applicant** | `applicant@example.com` | `password123` |

### Setting Up Additional Administrative Roles
To test specific administrative dashboards (Admissions, Cashier, or Scholarship Officer):
1. Log in as the **Super Administrator** using `admin@ttu.edu.ph`.
2. Go to **User Management** in the left sidebar dashboard.
3. Click **Create User** and create an account with the corresponding role (`admissions`, `cashier`, or `scholarship`).
4. Log out and sign in with the new credentials to access that specific portal.

---

## 📂 Key Directory Layout

* `admin/` — Pages and backend processing scripts for administrative roles (superadmin, admissions, cashier, scholarship).
* `applicant/` — Pages, forms, and handlers for the student enrollment process.
* `auth/` — Login, registration, and logout forms and backend processing.
* `config/` — Database DSN settings and PDO connection script.
* `database/` — Contains database structure DDL (`schema.sql`) and seed entries (`seed.sql`).
* `includes/` — Core security module (`auth.php`) containing session security, CSRF protection, and route guards; helper functions (`functions.php`) containing dynamic timeline computations.
* `public/` — Public landing page of the university.
* `uploads/` — Secure directory where applicant documents are stored.

---

## 🛡️ Security Features Implemented

* **SQL Injection Protection**: Every single query utilizes PDO prepared statements with real server-side preparation (`EMULATE_PREPARES = false`).
* **CSRF (Cross-Site Request Forgery) Protection**: Timing-safe verification of random cryptographic tokens generated per user session for all POST submissions.
* **Session Security**: Session IDs are regenerated on login to prevent fixation. IP address and User-Agent parameters are verified on every page load to prevent session hijacking.
* **Robust File Upload Restrictions**: File upload verification includes MIME-type extraction matching alongside whitelist configuration constraints (PDF, JPG, JPEG, PNG) capped at 5MB.
* **Strict Passwords**: The system enforces password composition verification requiring a minimum length of 8 characters, at least one uppercase letter, one lowercase letter, one numeric digit, and one special symbol.

---

## 🔍 Troubleshooting Guide

* **Database Connection Failed / "Unable to connect to the database"**
  * Verify that MySQL is running in your XAMPP control panel.
  * Check the port value in your configuration; MySQL default is usually `3306`.
  * Ensure the username and password in `config/database.php` match your actual MySQL database credentials.

* **Uploaded files fail to save / "Failed to move uploaded file"**
  * Make sure the `uploads/documents/` folder exists in your project root.
  * Verify that Apache has directory write permissions for the `uploads/` folder.

* **Blank white screen or Fatal PHP errors**
  * Check your PHP version (minimum requirement is PHP 8.0).
  * Inspect the Apache error logs (`C:\xampp\apache\logs\error.log`) or enable error display temporarily in your PHP environment.
