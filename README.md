# Triple T University (TTU) Enrollment & Learning Management System

![PHP Version](https://img.shields.io/badge/PHP-7.4%20|%208.x-blue.svg)
![Database](https://img.shields.io/badge/Database-MariaDB%20%2F%20MySQL-orange.svg)
![Framework](https://img.shields.io/badge/Architecture-Hybrid%20MVC%20Vanilla%20PHP-green.svg)
![Frontend](https://img.shields.io/badge/Frontend-Bootstrap%205.3%20%2B%20jQuery-purple.svg)

The **Triple T University (TTU) Enrollment & Learning Management System** is a monolithic web platform managing the full student academic lifecycle—from online application, 6-digit email OTP verification, document upload, and clinic medical clearance, to curriculum versioning, dynamic per-unit tuition assessment, cashier verification, automated credential issuance, and an integrated Student & Faculty Learning Management System (LMS).

---

## Key Features

1. **Self-Service Applicant Portal:**
   - Online registration with mandatory **6-Digit Email OTP Verification** (Google SMTP / PHPMailer).
   - Digital requirements upload with preview modal.
   - Medical history declaration and emergency contact submission.
   - Timetable selection and dynamic subject schedule builder.
   - Downloadable/printable Certificate of Matriculation & Assessment Slips.
2. **Administrative Management System:**
   - **Admissions:** Application intake, document verification, approval, and automated student number / institutional email provisioning.
   - **Clinic / Health:** Medical history evaluations and clearance gating.
   - **Registrar:** Versioned curriculum builder for **College Programs** & **Senior High School (SHS) Strands**, master subject catalog, and student masterlists.
   - **Scheduler:** Class section creation, faculty adviser assignments, timetable scheduling, and room capacity enforcement.
   - **Finance & Cashier:** Dynamic **Tuition Rate per Unit** calculation ($\text{Units} \times \text{Rate}$), cashier ledger, bank payment proof inspection, and official receipt issuance.
   - **Scholarships:** Program management, student grant reviews, and automated discount deductions.
   - **System Admin:** User management, granular permissions, database backup manager, activity audit logs, and demographic reports.
3. **Integrated Dual Learning Management System (LMS):**
   - Dual enrollment repository support for both **College** and **SHS** courses.
   - **Student LMS:** Enrolled course viewer, learning modules, assignments, timed multiple-choice quizzes, real-time attendance, and student gradebook.
   - **Faculty LMS:** Module file uploader, assignment creation & grading, quiz authoring engine with question banks, and attendance manager.
   - **Secure File Delivery:** Direct object download protection preventing unauthorized file leaks.

---

## Technology Stack

- **Backend:** Vanilla PHP (Custom PSR-4 Autoloader, Custom Router, Middleware Pipeline)
- **Database:** MariaDB 10.4+ / MySQL 8.0+ (Raw SQL via PDO with prepared statements, 41 relational tables)
- **Email Delivery:** PHPMailer (v6.9+) with Google SMTP TLS (Port 587)
- **Frontend:** Bootstrap 5.3, Vanilla CSS, JavaScript (ES6+), jQuery 3.7+, SweetAlert2, Chart.js
- **Web Server:** Apache 2.4 (XAMPP Environment) with `.htaccess` mod_rewrite

---

## Quickstart Setup Guide

### 1. Requirements
- XAMPP (Apache + MySQL/MariaDB)
- PHP 7.4 or 8.x with `pdo_mysql`, `openssl`, `mbstring`, `curl`
- Composer 2.x

### 2. Installation Steps
1. Place repository in `C:\xampp\htdocs\sia`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Create database in MySQL:
   ```sql
   CREATE DATABASE sia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Import active schema:
   ```bash
   mysql -u root -p sia < schema_dump.sql
   ```
5. Install dependencies:
   ```bash
   composer install
   ```
6. Configure `.env` in the project root:
   ```ini
   SMTP_HOST="smtp.gmail.com"
   SMTP_PORT="587"
   SMTP_ENCRYPTION="tls"
   SMTP_USERNAME="your-email@gmail.com"
   SMTP_PASSWORD="your-16-char-google-app-password"
   MAIL_FROM_ADDRESS="no-reply@ttu.edu.ph"
   MAIL_FROM_NAME="Triple T University"

   DB_HOST="localhost"
   DB_PORT="3306"
   DB_DATABASE="sia"
   DB_USERNAME="root"
   DB_PASSWORD=""

   APP_ENV="development"
   ```

---

## Standard Test Credentials

| Role | Username / ID / Email | Password | Access Route |
|---|---|---|---|
| **Superadmin** | `admin@ttu.edu.ph` | `admin123` | `/admin/dashboard.php` |
| **Admissions Officer** | `admissions@ttu.edu.ph` | `admin123` | `/admin/admissions/admissions_dashboard.php` |
| **Registrar Officer** | `registrar@ttu.edu.ph` | `admin123` | `/admin/registrar/registrar_dashboard.php` |
| **Cashier / Finance** | `cashier@ttu.edu.ph` | `admin123` | `/admin/finance/cashier_dashboard.php` |
| **Clinic Officer** | `clinic@ttu.edu.ph` | `admin123` | `/admin/clinic/clinic_dashboard.php` |
| **Scheduler** | `scheduler@ttu.edu.ph` | `admin123` | `/admin/scheduler/scheduler_dashboard.php` |
| **Scholarship Officer** | `scholarship@ttu.edu.ph` | `admin123` | `/admin/scholarship/scholarship_dashboard.php` |
| **Enrolled Student** | `2026-000003` (or applicant email) | `password123` | `/lms/student/dashboard.php` |
| **Faculty Instructor** | Faculty Employee ID | `password123` | `/lms/faculty/dashboard.php` |

---

## Documentation Index
The complete technical and architectural documentation is maintained in the [`docs/obsidian/`](file:///c:/xampp/htdocs/sia/docs/obsidian/) vault:
- **[[TTU Enrollment System Home]](file:///c:/xampp/htdocs/sia/docs/obsidian/00%20-%20Home/TTU%20Enrollment%20System%20Home.md)**: Master documentation hub.
- **[[System Architecture]](file:///c:/xampp/htdocs/sia/docs/obsidian/01%20-%20Architecture/System%20Architecture.md)**: Hybrid MVC framework & request lifecycle.
- **[[Data Dictionary]](file:///c:/xampp/htdocs/sia/docs/obsidian/04%20-%20Database/Data%20Dictionary.md)**: Full schema dictionary for all 41 tables.
- **[[Student Lifecycle Workflow]](file:///c:/xampp/htdocs/sia/docs/obsidian/03%20-%20Workflows/Student%20Lifecycle%20Workflow.md)**: Complete end-to-end lifecycle guide.
- **[[AI Development Context]](file:///c:/xampp/htdocs/sia/docs/obsidian/11%20-%20Development%20Guide/AI%20Development%20Context.md)**: Engineering guidelines and sensitive areas for AI assistants.

---
&copy; 2026 Triple T University. All rights reserved.
