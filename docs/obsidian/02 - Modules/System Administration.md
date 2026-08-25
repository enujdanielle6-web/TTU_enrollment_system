# System Administration Module

## 1. Overview
The **System Administration** module provides high-level control over institutional accounts, granular role-based permissions, comprehensive audit logging, database backup and restoration, global configuration parameters, and analytical reporting.

- **Primary Controllers:**
  - [`SystemController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/SystemController.php)
  - [`DashboardController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/DashboardController.php)
  - [`ReportController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/ReportController.php)
  - [`LmsAdminController`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/LmsAdminController.php)
- **Authorized Roles:** `superadmin`, `admin`
- **Key Views:**
  - `admin/system/sysadmin_dashboard.php`
  - `admin/system/users.php`
  - `admin/system/user_activity.php`
  - `admin/system/audit_logs.php`
  - `admin/system/backup.php`
  - `admin/system/settings.php`
  - `admin/system/reports.php`

---

## 2. Core Capabilities

### A. User Management (`/admin/system/users.php`)
- **User Creation:** Supports provisioning of institutional accounts (`applicant`, `superadmin`, `admissions`, `scholarship`, `cashier`, `clinic`, `faculty`, `scheduler`).
- **Password Strength Validation:** Enforces complex passwords via `isPasswordStrong()` (minimum 8 characters, uppercase, lowercase, numbers, and special symbols).
- **Role & Permission Assignment:** Assigns roles and custom JSON-encoded granular permission arrays to `users.permissions`.
- **Account Status Toggling:** Admins can activate or deactivate accounts (`users.is_active`). Self-deactivation is strictly prevented.
- **Password Reset:** Administrators can reset user passwords to the institutional default (`@Admin123`).

### B. Audit Trail & Activity Logs (`/admin/system/audit_logs.php`)
- Captures all critical state mutations in `activity_logs`.
- Logs IP address, affected entity record identifier, visual Bootstrap icon, action title, description, and JSON serialized `old_value` and `new_value` snapshots.
- User-specific activity history can be inspected via `/admin/system/user_activity.php?id={id}`.

### C. Database Backup & Restoration (`/admin/system/backup.php`)
- **Export (Backup):** Generates a full SQL dump (`backup_sia_YYYY-MM-DD_HHMMSS.sql`) iterating through all database tables, preserving schema definitions (`CREATE TABLE`) and row data (`INSERT INTO`), with foreign key checks temporarily disabled.
- **Import (Restore):** Accepts `.sql` backup files and executes the query script against the active MariaDB database connection.

### D. Global System Settings (`/admin/system/settings.php`)
- Manages key-value pairs stored in `system_settings`:
  - `active_school_year`: E.g., `2026-2027`.
  - `enrollment_status`: `open` or `closed`.
  - `college_cost_per_unit`: Default rate per unit for college tuition calculation.
- **Broadcast Announcements:** Posts system-wide announcements (`announcements` table) with custom badge labels and color accents.

---

## 3. Database Schema Dependencies
- `users`: Account identities and credentials.
- `activity_logs`: Immutable audit trail.
- `announcements`: Global broadcast notices.
- `system_settings`: Key-value configuration dictionary.

---

## 4. Security & Access Control
- Access to Backup & Restore and System Settings requires wildcard `*` superadmin authorization.
- All state-modifying actions validate CSRF tokens via `CsrfMiddleware` or inline `hash_equals()`.
- Passwords are encrypted using PHP `password_hash()` with `PASSWORD_DEFAULT` (bcrypt).

---
**Related:**
- [[System Architecture]]
- [[Users Table]]
- [[Reports Overview]]
- [[Security Overview]]
