# Business Rules

This document serves as the index for rules governing the TTU System.

## Confirmed Rules (Code/DB Enforced)
- **Unique Reference:** An applicant can only have one active `reference_number` at a time.
- **Role Isolation:** Middleware strictly enforces that a user with the `applicant` role cannot access `/admin` routes.
- **Assessment Dependency:** A student cannot be fully assessed unless a matching record exists in `fee_templates` for their level/strand.
- **Curriculum Locking:** An application is tied to a specific curriculum version, locking their subject requirements.

## Implied Rules (Based on Implementation)
- **Application = Term:** A student must undergo a new "Application" phase for every new school year/semester to generate a new enrollment and assessment record.
- **Scholarship Priority:** Scholarship discounts are calculated and deducted *before* the net assessment is finalized for payment.

## Missing / Undefined Rules
- **Prerequisites:** Clear, DB-enforced prerequisites for subjects.
- **Transferees:** Mapping of external credits to the internal curriculum.
- **Dropping/Refunds:** Logic for dropping subjects after payment has been made.

## Related
- [[Workflow Index]]
- [[Module Index]]
