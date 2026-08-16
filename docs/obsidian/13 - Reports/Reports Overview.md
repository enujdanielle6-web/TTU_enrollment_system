# Reports Overview

Reports are managed by the `App\Controllers\Admin\ReportController.php`.

## Generation Workflow
1. The admin selects a date range and report type.
2. The controller executes aggregate queries using raw SQL.
3. The results are parsed and pushed to a CSV generator or a printable HTML view.

## Export Capability
- `POST /admin/system/reports_export.php` generates a CSV file via setting headers:
  ```php
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="report.csv"');
  ```

## Current Reports Available
- **Enrollment Summary:** Counts of enrolled students per program/strand.
- **Financial Collection:** Aggregate totals from `payment_records` grouped by day/cashier.
- **Scholarship Disbursement:** List of active `student_scholarships` and their financial impact.
