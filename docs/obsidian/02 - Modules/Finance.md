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
- **Decoupled Payment Verification (`payment_verified`):** When an initial or full payment is verified, the assessment payment status updates to `partial` or `paid`, and the application status transitions to `payment_verified`. Cashier explicitly **does not** mark the student as `enrolled` or assign student credentials, routing the student to the Registrar's enrollment queue.
- **Financial Immutability via `assessment_items`:** Rather than re-querying live unit rates or curriculum subjects on every view, line items (tuition, miscellaneous, laboratory, registration, and discounts) are immutably snapshotted into `assessment_items` upon assessment generation. Both applicant and cashier views render from `assessment_items` with a "Locked / Finalized" badge.
- **Atomic Receipt Sequences (`receipt_sequences`):** Receipts are generated via `generateAtomicReceiptNumber()` using the dedicated `receipt_sequences` table and `INSERT ... ON DUPLICATE KEY UPDATE`, guaranteeing strictly monotonic and collision-free receipt numbers (`REC-YYYYMMDD-XXXX`).
- **Submit Debouncing:** Approve and Reject modal submit actions on `cashier_payments.php` feature instant debouncing with loading spinner states to prevent duplicate payment approvals or double-ledger transactions.

---

## 2. Core Endpoints & Actions
| Endpoint | Method | Controller & Action | Description |
|---|---|---|---|
| `/admin/finance/cashier_dashboard.php` | GET | `FinanceController@dashboard` | Financial KPI widgets, daily collections, payment verification queue. |
| `/admin/finance/cashier_assessment.php` | GET | `FinanceController@assessment` | Displays individual student assessment breakdown from immutable `assessment_items`. |
| `/admin/finance/cashier_payments.php` | GET | `FinanceController@payments` | Payment ledger and bank transfer proof verification queue with debounced modals. |
| `/admin/finance/cashier_receipt.php` | GET | `FinanceController@receipt` | Official printable payment receipt (OR) layout. |
| `/admin/finance/cashier_process.php` | POST | `FinanceController@process` | Records payments, verifies uploaded bank slips, generates atomic receipt numbers. |
| `/admin/finance/fees.php` | GET | `FeeController@index` | Fee templates management table by program/strand, year level, and semester. |
| `/admin/finance/fee_process.php` | POST | `FeeController@process` | Creates and updates fee templates with per-unit flags and semester scoping. |

---

## 3. Database Ledger Tables
- **`fee_templates`**: Standard fee matrix per academic level, program, year level, and semester.
- **`student_assessments`**: Stores the aggregate assessment records tied to an `application_id`.
- **`assessment_items`**: Immutable line-item snapshot table capturing tuition, misc, lab, registration, and discount line items.
- **`receipt_sequences`**: Concurrency-safe atomic sequence tracking table per calendar day (`sequence_date`, `current_value`).
- **`payment_records` / `payments`**: Individual payment transactions, receipt numbers (`REC-YYYYMMDD-XXXX`), payment channels (Cash, Bank Transfer, GCash), proof image paths, and verification statuses (`verified`, `pending`, `rejected`).

---
**Related:**
- [[Payment & Assessment Workflow]]
- [[Scholarship]]
- [[Applicant Portal]]
