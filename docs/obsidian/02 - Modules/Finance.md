# Finance & Cashier Module

**Path**: `admin/finance/`  
**Required Roles**: `cashier`, `admin`, `superadmin`  
**Controllers**: [`FinanceController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FinanceController.php), [`FeeController.php`](file:///c:/xampp/htdocs/sia/app/Controllers/Admin/Finance/FeeController.php)

The Finance & Cashier module governs student tuition assessment calculations, fee template management, payment recording, and receipt generation.

---

## 1. Dynamic Tuition Rate per Unit Calculation
The system computes student tuition based on actual enrolled units:

$$\text{Total Assessment} = (\text{Total Enrolled Units} \times \text{Tuition Rate per Unit}) + \text{Miscellaneous Fees} + \text{Registration Fee} + \text{Lab Fee} + \text{Other Fees} - \text{Scholarship Discount}$$

### Implementation Logic
- **`fee_templates` Table:** Features `is_per_unit` (TINYINT(1) DEFAULT 0) and `semester` (ENUM('First','Second','Summer') DEFAULT NULL).
- **Per-Unit Mode (`is_per_unit = 1`):** The `tuition_fee` column represents the *rate per unit* (e.g. ₱500.00/unit). The controller queries `college_enrollments` or `shs_enrollments` joined with `subjects`, sums the total units (e.g. 18 units), and multiplies by the rate.
- **Fixed Rate Mode (`is_per_unit = 0`):** Legacy fallback where `tuition_fee` acts as a static flat fee.
- **SHS & College Compatibility:** Queries both `college_enrollments` and `shs_enrollments`, ensuring SHS strands and College degrees compute accurate breakdowns.
- **Automated Enrollment Finalization:** When a payment is recorded or verified that brings `total_paid >= ₱3,000` (or full balance), the system triggers `finalizeStudentEnrollment()`, transitioning `applications.status = 'enrolled'`, assigning a student number, and issuing institutional credentials.
- **Receipt Numbering Format:** Official payment receipts are automatically generated in the format `REC-YYYYMMDD-XXXX` (e.g., `REC-20260825-0001`).

---

## 2. Core Endpoints & Actions
| Endpoint | Method | Controller & Action | Description |
|---|---|---|---|
| `/admin/finance/cashier_dashboard.php` | GET | `FinanceController@dashboard` | Financial KPI widgets, daily collections, payment verification queue. |
| `/admin/finance/cashier_assessment.php` | GET | `FinanceController@assessment` | Displays individual student assessment breakdown with itemized calculation. |
| `/admin/finance/cashier_payments.php` | GET | `FinanceController@payments` | Payment ledger and bank transfer proof verification queue with modal preview. |
| `/admin/finance/cashier_receipt.php` | GET | `FinanceController@receipt` | Official printable payment receipt (OR) layout. |
| `/admin/finance/cashier_process.php` | POST | `FinanceController@process` | Records payments, verifies uploaded bank slips, updates `payment_records`. |
| `/admin/finance/fees.php` | GET | `FeeController@index` | Fee templates management table by program/strand, year level, and semester. |
| `/admin/finance/fee_process.php` | POST | `FeeController@process` | Creates and updates fee templates with per-unit flags and semester scoping. |

---

## 3. Database Ledger Tables
- **`fee_templates`**: Standard fee matrix per academic level, program, year level, and semester.
- **`student_assessments`**: Stores the finalized assessment records tied to an `application_id`.
- **`payment_records`**: Individual payment transactions, receipt numbers (`REC-YYYYMMDD-XXXX`), payment channels (Cash, Bank Transfer, GCash), proof image paths, and verification statuses (`verified`, `pending`, `rejected`).

---
**Related:**
- [[Payment & Assessment Workflow]]
- [[Scholarship]]
- [[Applicant Portal]]
