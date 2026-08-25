# Triple T University (TTU) Enrollment & Learning Management System

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)
![Database](https://img.shields.io/badge/Database-MariaDB%20%2F%20MySQL%20(42%20Tables)-orange.svg)
![Framework](https://img.shields.io/badge/Architecture-Hybrid%20MVC%20Vanilla%20PHP-green.svg)
![Frontend](https://img.shields.io/badge/Frontend-Bootstrap%205.3%20%2B%20Vanilla%20CSS-purple.svg)

The **Triple T University (TTU) Enrollment & Learning Management System** is a monolithic web application managing the full student academic lifecycle—from online application, 6-digit email OTP verification, document upload, and clinic medical clearance, to curriculum versioning, dynamic per-unit tuition assessment, cashier ledger verification, automated student number issuance, and an integrated Student & Faculty Learning Management System (LMS).

---

## Key Subsystems & Features

1. **Self-Service Applicant Portal:**
   - Online registration with mandatory **6-Digit Email OTP Verification** (Google SMTP / PHPMailer).
   - Multi-step application submission with dynamic requirements uploader.
   - Medical history declaration, emergency contacts, and clinical clearance status tracking.
   - Timetable selection and section enrollment builder.
   - Real-time tuition assessment breakdowns and bank transfer proof upload.
2. **Administrative Management System:**
   - **Admissions:** Application intake queue, document verification modal, section assignment, and automated student number / institutional email provisioning.
   - **Clinic / Health:** Medical declaration audits, health condition tagging, and clinic clearance gating.
   - **Registrar:** Versioned curriculum builder for **College Programs** & **Senior High School (SHS) Strands**, master subject catalog, and enrolled student masterlist exports.
   - **Scheduler:** Class section block creation, faculty adviser assignments, timetable scheduling, and room conflict prevention.
   - **Finance & Cashier:** Dynamic **Tuition Rate per Unit** calculation ($\text{Units} \times \text{Rate}$), cashier ledger, online payment verification, and official receipt (`REC-YYYYMMDD-XXXX`) generation.
   - **Scholarships:** Scholarship catalog, applicant review queue, and automated assessment discount deductions.
   - **System Admin:** User account management, granular role permissions, native SQL database backup/restore manager, activity audit logs with old/new diffs, and demographic reports.
3. **Integrated Dual-Tier Learning Management System (LMS):**
   - JIT course auto-provisioning from dual enrollment repositories (**College** & **SHS**).
   - **Student LMS:** Enrolled course viewer, learning modules, downloadable materials, assignment submission uploader, timed multiple-choice quizzes, attendance tracking, and gradebook.
   - **Faculty LMS:** Assigned section rosters, module file manager, assignment authoring & grading with feedback, quiz authoring engine with question banks, and attendance logging.
   - **Secure File Delivery:** Protected media routing preventing unauthorized asset discovery.

---

## Technology Stack

- **Backend:** Vanilla PHP 8.2+ (Custom PSR-4 Autoloader, Custom Router with Dynamic Parameter Binding, Middleware Pipeline)
- **Database:** MariaDB 10.4+ / MySQL 8.0+ (Raw SQL via PDO with prepared statements, 42 relational tables and views)
- **Email Delivery:** PHPMailer (v6.9+) with Google SMTP TLS (Port 587)
- **Frontend:** Bootstrap 5.3, Vanilla CSS, JavaScript (ES6+), SweetAlert2, Chart.js
- **Web Server:** Apache 2.4 (XAMPP Environment) with `.htaccess` mod_rewrite

---

## Quickstart Setup Guide

### 1. Requirements
- XAMPP with PHP 8.2+ (Apache + MySQL/MariaDB)
- Extensions: `pdo_mysql`, `openssl`, `mbstring`, `curl`
- Composer 2.x

### 2. Installation Steps
1. Place repository in `C:\xampp\htdocs\sia`.
2. Start **Apache** and **MySQL** in your XAMPP Control Panel.
3. Install Composer dependencies:
   ```bash
   composer install
   ```
4. Configure environment variables by creating `.env` in the project root:
   ```ini
   DB_HOST="localhost"
   DB_PORT="3306"
   DB_DATABASE="sia"
   DB_USERNAME="root"
   DB_PASSWORD=""

   SMTP_HOST="smtp.gmail.com"
   SMTP_PORT="587"
   SMTP_ENCRYPTION="tls"
   SMTP_USERNAME="your-email@gmail.com"
   SMTP_PASSWORD="your-16-char-google-app-password"
   MAIL_FROM_ADDRESS="no-reply@ttu.edu.ph"
   MAIL_FROM_NAME="Triple T University"

   APP_ENV="development"
   ```

### 3. Automated Database Setup (Recommended)
Run the automated setup engine via CLI:
```bash
php database/migrations/setup_database.php
```
*(Or navigate to `http://localhost/sia/database/migrations/setup_database.php` in your browser).*

This automated script will:
- Initialize database `sia` with `utf8mb4_unicode_ci` collation.
- Import the complete 42-table schema and views from `database/schema.sql`.
- Seed standard institutional accounts, degree programs, subjects, versioned curricula, class sections, fee templates, scholarships, sample students, and interactive LMS courses from `database/seed.sql`.

#### Alternative Manual Database Import:
```bash
mysql -u root sia < database/schema.sql
mysql -u root sia < database/seed.sql
```

---

## Standard Test Credentials

| Role | Identifier / Email | Password | Access Route |
|---|---|---|---|
| **Superadmin** | `admin@ttu.edu.ph` | `admin123` | `/admin/dashboard.php` |
| **Admissions Officer** | `admissions@ttu.edu.ph` | `admin123` | `/admin/admissions/admissions_dashboard.php` |
| **Registrar Officer** | `registrar@ttu.edu.ph` | `admin123` | `/admin/registrar/registrar_dashboard.php` |
| **Cashier / Finance** | `cashier@ttu.edu.ph` | `admin123` | `/admin/finance/cashier_dashboard.php` |
| **Clinic Officer** | `clinic@ttu.edu.ph` | `admin123` | `/admin/clinic/clinic_dashboard.php` |
| **Scheduler** | `scheduler@ttu.edu.ph` | `admin123` | `/admin/scheduler/scheduler_dashboard.php` |
| **Scholarship Officer** | `scholarship@ttu.edu.ph` | `admin123` | `/admin/scholarship/scholarship_dashboard.php` |
| **Faculty Instructor** | `FAC-2026-001` *(or `alan.turing@ttu.edu.ph`)* | `password123` | `/lms/faculty/dashboard.php` |
| **Enrolled College Student** | `2026-000001` *(or `john.doe@example.com`)* | `password123` | `/lms/student/dashboard.php` |
| **Enrolled SHS Student** | `2026-000002` *(or `mary.smith@example.com`)* | `password123` | `/lms/student/dashboard.php` |
| **Applicant User** | `jane.applicant@example.com` | `password123` | `/applicant/dashboard.php` |

---

## Documentation Vault

The complete technical and architectural documentation is maintained in the [`docs/obsidian/`](file:///c:/xampp/htdocs/sia/docs/obsidian/) vault:
- **[Master Hub](file:///c:/xampp/htdocs/sia/docs/obsidian/00%20-%20Home/TTU%20Enrollment%20System%20Home.md)**: Central launchpad for all 17 documentation domains.
- **[System Architecture](file:///c:/xampp/htdocs/sia/docs/obsidian/01%20-%20Architecture/System%20Architecture.md)**: Monolithic Hybrid MVC structure, routing, and request lifecycle.
- **[Entity Relationship Architecture](file:///c:/xampp/htdocs/sia/docs/obsidian/04%20-%20Database/Entity%20Relationship%20Architecture.md)**: High-level and domain-level Mermaid ER diagrams, cardinality, and foreign key topology.
- **[Data Dictionary](file:///c:/xampp/htdocs/sia/docs/obsidian/04%20-%20Database/Data%20Dictionary.md)**: Complete column specifications for all 42 tables and views.
- **[Page Relationships & Dependency Maps](file:///c:/xampp/htdocs/sia/docs/obsidian/16%20-%20Page%20Relationships/00%20-%20Master%20Relationship%20Index%20&%20Matrix.md)**: End-to-end trace from View $\rightarrow$ Route $\rightarrow$ Controller $\rightarrow$ Service $\rightarrow$ Database.
- **[Troubleshooting Runbook](file:///c:/xampp/htdocs/sia/docs/obsidian/15%20-%20Operations/Troubleshooting%20Runbook.md)**: Common runtime error fixes and SMTP debugging guide.

---

&copy; 2026 Triple T University. All rights reserved.
