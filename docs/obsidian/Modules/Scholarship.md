# Scholarship Module

**Path**: `admin/scholarship/`
**Role Required**: `scholarship` or `superadmin`

The Scholarship module allows the university to manage and distribute financial aid to applicants during the [[Enrollment Workflow]].

## Core Responsibilities
1. **Manage Grants**: CRUD operations on the `scholarships` table to define available grants (e.g., Academic Honors, Athletic, LGU) and their specific discount amounts (fixed or percentage).
2. **Review Applications**: Applicants can apply for a scholarship from their dashboard. The Coordinator reviews these via `scholarships.php` and approves or rejects them based on submitted requirements.

## Data Flow
If a student's `scholarship_applications` record is marked as "Approved", this data is passed to the [[Modules/Finance]] module. When the Cashier generates the bill, the `assessments` table automatically incorporates the scholarship's discount value, reducing the `net_amount` owed by the student.
