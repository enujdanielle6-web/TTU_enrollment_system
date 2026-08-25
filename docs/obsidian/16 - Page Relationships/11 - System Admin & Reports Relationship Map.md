# System Admin & Reports Relationship Map

This document traces the code execution chains, administrative user management, immutable audit logging, SQL database backup/restore engines, global system configuration, and analytical reports.

---

## 1. System Administration Master Dashboard (`/admin/dashboard.php`)

### Page Identity
- **File Path:** [`app/Views/admin/system/dashboard.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/system/dashboard.php)
- **Controller:** [`app/Controllers/Admin/System/DashboardController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/DashboardController.php) (`index()`)
- **Route:** `GET /admin/dashboard.php`
- **Authorized Roles:** `admin`, `superadmin`
- **Middleware:** `SessionSecurityMiddleware`, `AuthMiddleware`, `RoleMiddleware:admin,superadmin`

### Database Tracing Chain
```text
GET /admin/dashboard.php
    ↓
DashboardController@index
    ↓
1. SELECT COUNT(*) as total_apps FROM applications
2. SELECT COUNT(*) as enrolled_students FROM applications WHERE status = 'enrolled'
3. SELECT SUM(amount) as total_revenue FROM payment_records WHERE status = 'verified'
4. SELECT COUNT(*) as pending_tasks FROM applications WHERE status = 'pending'
5. SELECT academic_level, COUNT(*) as count FROM applications GROUP BY academic_level
6. SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8
    ↓
Renders Chart.js enrollment analytics and KPI cards
```

---

## 2. Institutional User Management (`/admin/system/users.php`)

### Page Identity
- **File Path:** [`app/Views/admin/system/users.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/system/users.php)
- **Controller:** [`app/Controllers/Admin/System/SystemController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/SystemController.php) (`users()`, `saveUser()`, `resetUserPassword()`, `toggleUserStatus()`)
- **Routes:** `GET /admin/system/users.php`, `POST /admin/system/user_process.php`

### Tracing Chain & Password Enforcement
```text
POST /admin/system/user_process.php (first_name, last_name, email, role, department, permissions[])
    ↓
SystemController@saveUser
    ↓
Validation:
    ├── Unique email verification in users table
    └── isPasswordStrong($password)
    ↓
Hash: password_hash($password, PASSWORD_DEFAULT)
    ↓
JSON encode permissions array: json_encode($permissions)
    ↓
INSERT INTO users (first_name, last_name, email, password, role, department, permissions, email_verified, is_active)
VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)
    ↓
logActivity($adminId, 'User Account Created', 'Created user ' . $email . ' with role ' . $role)
    ↓
Redirect: /admin/system/users.php
```

---

## 3. System Audit Trail (`/admin/system/audit_logs.php`)

### Page Identity
- **File Path:** [`app/Views/admin/system/audit_logs.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/system/audit_logs.php)
- **Controller:** `SystemController@auditLogs`
- **Route:** `GET /admin/system/audit_logs.php`

### Data Flow
Queries `activity_logs` joined with `users` to display an immutable chronological log of state changes. Features interactive modal for inspecting `old_value` and `new_value` JSON snapshots.

---

## 4. SQL Database Backup & Restore (`/admin/system/backup.php`)

### Page Identity
- **File Path:** [`app/Views/admin/system/backup.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/system/backup.php)
- **Controller:** `SystemController@backup`, `SystemController@exportBackup`, `SystemController@importBackup`
- **Routes:** `GET /admin/system/backup.php`, `GET /admin/system/backup_export.php`, `POST /admin/system/backup_import.php`

### Backup Export Engine Traced
```text
GET /admin/system/backup_export.php
    ↓
SystemController@exportBackup
    ↓
1. Query SHOW FULL TABLES (Fetches all 42 tables and views)
2. Disable foreign key checks: SET FOREIGN_KEY_CHECKS = 0;
3. For each table:
   ├── Query SHOW CREATE TABLE `{tableName}`
   └── Fetch all rows: SELECT * FROM `{tableName}` and generate INSERT INTO statements
4. Enable foreign key checks: SET FOREIGN_KEY_CHECKS = 1;
5. Stream download headers:
   ├── Content-Type: application/sql
   └── Content-Disposition: attachment; filename="backup_sia_YYYY-MM-DD_HHMMSS.sql"
```

---

## 5. Global System Settings (`/admin/system/settings.php`)

### Page Identity
- **File Path:** [`app/Views/admin/system/settings.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/system/settings.php)
- **Controller:** `SystemController@settings`, `SystemController@saveSettings`
- **Routes:** `GET /admin/system/settings.php`, `POST /admin/system/settings_save.php`

### Tracing Chain
```text
POST /admin/system/settings_save.php (active_school_year, enrollment_status, college_cost_per_unit)
    ↓
SystemController@saveSettings
    ↓
Loop through key-value pairs:
    INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ↓
logActivity($adminId, 'Settings Updated', 'Modified global system settings')
    ↓
Redirect: /admin/system/settings.php
```

---

## 6. Analytical Reports & CSV Export (`/admin/system/reports.php`)

### Page Identity
- **File Path:** [`app/Views/admin/system/reports.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/system/reports.php)
- **Controller:** [`app/Controllers/Admin/System/ReportController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/ReportController.php) (`index()`, `export()`)
- **Routes:** `GET /admin/system/reports.php`, `POST /admin/system/reports_export.php`

### Export Tracing Chain
```text
POST /admin/system/reports_export.php (report_type ['enrollment'|'financial'|'scholarship'|'clinic'])
    ↓
ReportController@export
    ↓
Queries corresponding domain tables:
    ├── [enrollment]: applications JOIN college_programs / shs_strands
    ├── [financial]: payment_records JOIN student_assessments
    ├── [scholarship]: scholarship_recipients JOIN scholarships
    └── [clinic]: health_records JOIN applications
    ↓
Generates CSV stream with fputcsv()
```

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[01 - Shared Dependencies & Impact Analysis]]
- [[05 - Admissions Admin Relationship Map]]
