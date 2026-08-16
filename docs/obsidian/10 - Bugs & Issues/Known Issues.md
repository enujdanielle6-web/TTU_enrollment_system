# Known Issues

This index tracks architectural debt and critical bugs discovered during system analysis.

## 1. The 14MB Fat Controller Anomaly
- **Status:** RESOLVED
- **Module:** [[Admissions Module]]
- **File:** `app/Controllers/Admin/AdmissionsController_clean.php`
- **Description:** This file was approximately 13.9 MB in size due to a script loop anomaly.
- **Resolution:** The unused file has been completely deleted.

## 2. Fat Controller Architecture
- **Status:** RESOLVED (Proof of Concept Implemented)
- **Description:** Controllers bypassed the Model layer using raw SQL.
- **Resolution:** A robust `BaseModel` was implemented using the Active Record pattern. `User` and `Application` models now extend it, and a Proof of Concept refactor was completed in `ApplicantController`.

## 3. Missing Central Academic Records
- **Status:** RESOLVED
- **Description:** Student history was scattered across multiple term-based `applications` records.
- **Resolution:** The `student_academic_records_view` MySQL View was created to unify the complete transcript history.

## 4. Legacy Root Scripts
- **Status:** RESOLVED
- **Description:** Utility scripts were dangerously exposed in the root folder.
- **Resolution:** All 14 utility scripts were securely moved to a new `/scripts` directory.

## Related
- [[System Architecture]]
- [[MVC Strangler Fig Migration]]
