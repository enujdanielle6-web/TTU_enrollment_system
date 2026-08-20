# Reports Overview

**Path**: `admin/system/reports.php`  
**Required Roles**: `admin`, `superadmin`  
**Controller**: [`ReportController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/System/ReportController.php)

The Reports engine generates administrative summaries, demographic statistics, financial collection reports, and downloadable CSV exports.

---

## 1. Available System Reports

| Report Category | Description | Data Source Tables |
|---|---|---|
| **Enrollment Demographics** | Enrollment distribution by academic level (College / SHS), program/strand, year level, and gender breakdown. | `applications`, `college_programs`, `shs_strands` |
| **Financial Collections** | Cashier collections, payment channel distribution (Cash, Bank Transfer, GCash), daily totals, and verified receipts. | `payment_records`, `student_assessments`, `users` |
| **Scholarship Impact** | Total tuition discounts awarded, active scholars count, and budget allocation per scholarship program. | `scholarships`, `student_scholarships`, `applications` |
| **Medical / Clinic Clearance** | Compliance status of student health records (verified vs. pending medical evaluation). | `health_records`, `applications` |
| **Academic Performance & LMS** | LMS course enrollment totals and active student participation metrics. | `lms_courses`, `college_enrollments`, `shs_enrollments` |

---

## 2. Export Endpoints
- **CSV Export Endpoint:** `POST /admin/system/reports_export.php`
- **Mechanism:** Direct stream generation setting CSV headers:
  ```php
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="ttu_report_' . date('Y-m-d') . '.csv"');
  ```

---
**Related:**
- [[Finance]]
- [[Registrar]]
- [[Module Index]]
