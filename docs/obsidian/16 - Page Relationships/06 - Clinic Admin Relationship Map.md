# Clinic Admin Relationship Map

This document traces the complete code execution chain, medical clearance workflows, database queries, and role-based gating for the Clinic Administration subsystem.

---

## 1. Clinic Dashboard (`/admin/clinic/clinic_dashboard.php`)

### Page Identity
- **File Path:** [`app/Views/admin/clinic/clinic_dashboard.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/clinic/clinic_dashboard.php)
- **Controller:** [`app/Controllers/Admin/Clinic/ClinicController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Clinic/ClinicController.php) (`dashboard()`)
- **Route:** `GET /admin/clinic/clinic_dashboard.php`
- **Authorized Roles:** `clinic`, `admin`, `superadmin`
- **Middleware:** `SessionSecurityMiddleware`, `AuthMiddleware`, `RoleMiddleware:clinic`

### Database Tracing Chain
```text
GET /admin/clinic/clinic_dashboard.php
    ↓
ClinicController@dashboard
    ↓
1. SELECT COUNT(*) as total FROM health_records
2. SELECT COUNT(*) as pending FROM health_records WHERE status = 'pending'
3. SELECT COUNT(*) as verified FROM health_records WHERE status = 'verified'
4. SELECT COUNT(*) as with_conditions FROM health_records WHERE medical_conditions IS NOT NULL AND medical_conditions != ''
5. SELECT h.*, u.first_name, u.last_name, u.email, a.reference_number 
   FROM health_records h 
   JOIN users u ON h.user_id = u.id 
   JOIN applications a ON h.application_id = a.id 
   ORDER BY h.updated_at DESC LIMIT 10
    ↓
Renders: app/Views/admin/clinic/clinic_dashboard.php
```

---

## 2. Medical Clearance Queue (`/admin/clinic/medical_clearance.php`)

### Page Identity
- **File Path:** [`app/Views/admin/clinic/medical_clearance.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/clinic/medical_clearance.php)
- **Controller:** `ClinicController@clearance`
- **Route:** `GET /admin/clinic/medical_clearance.php`
- **Filters:** `status` (`pending`, `under_review`, `verified`, `correction_required`), `search`

---

## 3. Medical Record Detail & Examination (`/admin/clinic/medical_detail.php`)

### Page Identity
- **File Path:** [`app/Views/admin/clinic/medical_detail.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/clinic/medical_detail.php)
- **Controller:** `ClinicController@detail`
- **Route:** `GET /admin/clinic/medical_detail.php?id={id}`

### Tracing Chain
```text
GET /admin/clinic/medical_detail.php?id={id}
    ↓
ClinicController@detail
    ↓
SELECT h.*, u.first_name, u.last_name, u.email, a.reference_number, a.academic_level, a.grade_level, a.strand
FROM health_records h
JOIN users u ON h.user_id = u.id
JOIN applications a ON h.application_id = a.id
WHERE h.id = ?
    ↓
Renders physical measurements (height, weight, blood type), emergency contacts, and medical condition tags
```

---

## 4. Medical Clearance Processing (`/admin/clinic/medical_process.php`)

### Page Identity
- **Handler:** `ClinicController@process`
- **Route:** `POST /admin/clinic/medical_process.php`
- **Form:** `<form method="POST" action="/admin/clinic/medical_process.php">`

### Tracing Chain & State Mutation
```text
POST /admin/clinic/medical_process.php (health_record_id, status ['verified'|'correction_required'|'under_review'], admin_remarks)
    ↓
ClinicController@process
    ↓
Validation: Valid status enum check
    ↓
Database Operations:
    ├── 1. PDO UPDATE health_records SET status = ?, admin_remarks = ? WHERE id = ?
    └── 2. logActivity($adminId, 'Medical Clearance Updated', 'Updated health record #' . $id . ' to ' . $status)
    ↓
Flash Notification via $_SESSION['success_message']
    ↓
Redirect: /admin/clinic/medical_clearance.php
```

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[04 - Applicant Portal Relationship Map]]
- [[05 - Admissions Admin Relationship Map]]
