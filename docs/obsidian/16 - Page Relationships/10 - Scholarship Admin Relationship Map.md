# Scholarship Admin Relationship Map

This document traces the code execution chains, scholarship program administration, application review workflows, and automatic assessment discount recalculations.

---

## 1. Scholarship Dashboard (`/admin/scholarship/scholarship_dashboard.php`)

### Page Identity
- **File Path:** [`app/Views/admin/scholarship/scholarship_dashboard.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/scholarship/scholarship_dashboard.php)
- **Controller:** [`app/Controllers/Admin/Scholarship/ScholarshipController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Scholarship/ScholarshipController.php) (`dashboard()`)
- **Route:** `GET /admin/scholarship/scholarship_dashboard.php`
- **Authorized Roles:** `scholarship`, `admin`, `superadmin`
- **Middleware:** `SessionSecurityMiddleware`, `AuthMiddleware`, `RoleMiddleware:scholarship,admin,superadmin`

### Database Tracing Chain
```text
GET /admin/scholarship/scholarship_dashboard.php
    ↓
ScholarshipController@dashboard
    ↓
1. SELECT COUNT(*) as total_programs FROM scholarships WHERE status = 'Active'
2. SELECT COUNT(*) as pending_applications FROM scholarship_applications WHERE status = 'pending'
3. SELECT COUNT(*) as active_scholars FROM scholarship_recipients WHERE status = 'Active'
4. SELECT SUM(discount_amount) as total_discounts_granted FROM student_assessments
5. SELECT sa.*, u.first_name, u.last_name, s.name as scholarship_name
   FROM scholarship_applications sa
   JOIN users u ON sa.user_id = u.id
   JOIN scholarships s ON sa.scholarship_id = s.id
   ORDER BY sa.created_at DESC LIMIT 10
    ↓
Renders budget metrics, active grant slots, and recent applications
```

---

## 2. Scholarship Program Management (`/admin/scholarship/scholarships.php`)

### Page Identity
- **File Path:** [`app/Views/admin/scholarship/scholarships.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/scholarship/scholarships.php)
- **Controller:** `ScholarshipController@index`, `ScholarshipController@process`
- **Routes:** `GET /admin/scholarship/scholarships.php`, `POST /admin/scholarship/scholarship_process.php`

### Tracing Chain
```text
POST /admin/scholarship/scholarship_process.php
  (name, code, category, provider, tuition_coverage_type, tuition_coverage_value, misc_coverage_type, misc_coverage_value, stipend_amount)
    ↓
ScholarshipController@process
    ↓
Database Operation:
    ├── INSERT INTO scholarships (name, code, category, provider, tuition_coverage_type, tuition_coverage_value, ...)
    │   VALUES (?, ?, ?, ?, ?, ?, ...)
    └── logActivity($adminId, 'Scholarship Created', 'Created scholarship ' . $name)
    ↓
Redirect: /admin/scholarship/scholarships.php
```

---

## 3. Grant Application Review & Discount Recalculation (`/admin/scholarship/scholarship_review.php`)

### Page Identity
- **File Path:** [`app/Views/admin/scholarship/scholarship_review.php`](file:///c:/xampp/htdocs/sia/app/Views/admin/scholarship/scholarship_review.php)
- **Controller:** `ScholarshipController@review`, `ScholarshipController@processReview`
- **Routes:** `GET /admin/scholarship/scholarship_review.php?id={id}`, `POST /admin/scholarship/scholarship_process_review.php`

### Tracing Chain & Assessment Math
```mermaid
flowchart TD
    Officer[Scholarship Officer] -->|Approves Grant| Submit[POST /admin/scholarship/scholarship_process_review.php]
    Submit --> Controller[ScholarshipController@processReview]
    Controller --> DB1[UPDATE scholarship_applications SET status='approved']
    Controller --> DB2[INSERT INTO scholarship_recipients (user_id, scholarship_id, status='Active')]
    Controller --> Calc[Calculate Discount based on Scholarship Coverage]
    Calc --> DB3[UPDATE student_assessments SET scholarship_id=?, discount_amount=?, net_amount = (total_amount - discount_amount)]
    DB3 --> Log[INSERT INTO activity_logs (action='Scholarship Awarded')]
    Log --> Redirect[Redirect -> /admin/scholarship/scholarship_dashboard.php]
```

---
**Related:**
- [[00 - Master Relationship Index & Matrix]]
- [[09 - Finance & Cashier Relationship Map]]
- [[04 - Applicant Portal Relationship Map]]
