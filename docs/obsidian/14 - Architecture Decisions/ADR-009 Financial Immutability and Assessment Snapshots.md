# ADR-009: Financial Immutability and Assessment Item Snapshots

## Status
**Accepted**

## Date
2026-09-06

## Context
Previously, student tuition assessments in `student_assessments` stored only coarse scalar values: `tuition_fee`, `miscellaneous_fee`, `registration_fee`, `laboratory_fee`, `other_fees`, and `total_amount`. Detailed line items (individual course unit rates, lab breakdown, registration items) were calculated dynamically on-the-fly from active templates in `fee_templates` or settings in `system_settings`.

This approach introduced severe financial audit liabilities:
1. **Retroactive Recalculation:** If an administrator modified the per-unit tuition rate or laboratory fee schedule mid-term or in subsequent years, historical student assessments, balance calculations, and official receipts retroactively recalculated to match the new rates.
2. **Official Receipt Instability:** Cashier official receipts and historical student ledgers could not guarantee accurate historical itemization.
3. **Receipt Collisions:** Cashier receipt numbers were generated using non-atomic heuristic queries (`COUNT(*)+1` or timestamp-based strings), resulting in duplicate receipt numbers under concurrent cashier window transactions.

## Decision
We established a strict **Financial Immutability Architecture** centered on frozen line-item snapshots and atomic receipt sequences:

1. **Dedicated `assessment_items` Snapshot Table:**
   - Created the `assessment_items` table with foreign key linkage to `student_assessments.id` (`ON DELETE CASCADE`).
   - Columns: `id`, `assessment_id`, `item_type` (`tuition`, `miscellaneous`, `laboratory`, `registration`, `other`, `discount`), `item_code`, `item_name`, `units` (DECIMAL(4,2)), `rate_per_unit` (DECIMAL(10,2)), `amount` (DECIMAL(10,2)), `created_at`.
   - When an assessment is generated via `AssessmentService::generateAssessment()`, every line item is permanently inserted into `assessment_items`.
2. **Immutable Receipt & Billing Reads:**
   - Cashier receipt views (`cashier_receipt.php` rendering `receipt.php`) and student billing breakdowns read directly from `assessment_items`.
   - Fee template modifications or per-unit price increases have zero retroactive effect on existing assessments.
3. **Atomic Receipt Number Sequences:**
   - Created the dedicated sequence table `receipt_sequences` (`id` PK AUTO_INC, `sequence_year` UNIQUE, `current_value`, `updated_at`).
   - Implemented `generateAtomicReceiptNumber(PDO $pdo)` in `app/Helpers/functions.php` utilizing atomic sequence increment (`INSERT INTO receipt_sequences (sequence_year, current_value) VALUES (:year, 1) ON DUPLICATE KEY UPDATE current_value = current_value + 1`).
   - Generates standardized, collision-proof receipt numbers formatted as `REC-YYYYMMDD-XXXX`.

```text
Student Assessment Creation:
[Admissions/Finance Assessment Trigger]
         ↓
AssessmentService::generateAssessment()
         ↓
INSERT INTO student_assessments (tuition_fee, misc_fee, total_amount, ...)
         ↓
snapshotAssessmentItems()
         ↓
INSERT INTO assessment_items (assessment_id, item_type, item_code, item_name, units, rate_per_unit, amount)
[PERMANENTLY FROZEN LINE-ITEM RECORD]
```

## Consequences

### Positive
- **Complete Financial Auditability:** Historical assessments and printed official receipts reflect the exact statutory rates in effect at the moment of assessment generation.
- **Concurrency Safety:** Cashier desks can process payments simultaneously without risk of duplicate official receipt numbers.
- **Transparency:** Line items clearly demarcate lecture units, lab fees, and miscellaneous charges.

### Trade-offs
- Modest database growth due to normalized line items in `assessment_items` (approx. 4–8 rows per student per term).
- Handled efficiently with composite indexes on `assessment_items(assessment_id, item_type)`.

---
**Related:**
- [[Payment & Assessment Workflow]]
- [[Finance]]
- [[Database Overview]]
- [[Data Dictionary]]
- [[ADR-008 Authoritative Enrollment State Machine and Cashier Decoupling]]
- [[ADR-010 Domain Service Layer Extraction and Atomic Sequences]]
