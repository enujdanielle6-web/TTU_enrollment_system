# Finance Module

**Path**: `admin/finance/`
**Role Required**: `cashier` or `superadmin`

The Finance module handles the monetary aspect of the [[Enrollment Workflow]]. It relies on the `fee_templates`, `assessments`, and `payments` tables in the [[Database Schema]].

## Core Responsibilities
1. **Fee Templates**: Define the cost of tuition, miscellaneous fees, and lab fees for different programs and year levels (`fee_templates.php`).
2. **Assessment Generation**: When an applicant is cleared by [[Modules/Admissions]] and [[Modules/Clinic]], the Cashier generates a bill (`assessments.php`). 
3. **Scholarship Application**: If the student has an approved grant from [[Modules/Scholarship]], the assessment automatically deducts the discount.
4. **Payment Processing**: Cashiers log over-the-counter payments or verify online bank transfers (`payments.php`).

## Data Flow
Once an applicant's `assessments` record receives enough `payments` to cover the mandatory downpayment (e.g., Minimum ₱3,000), their application status is updated to allow the [[Modules/Registrar]] to finalize their enrollment.

*Related*: [[User Roles]]
