# Testing Strategy

`Status: Missing / Planned`

Currently, there are no PHPUnit tests or systematic automated tests discovered in the codebase. Testing is entirely manual.

## Recommended Testing Strategy
Due to the system's reliance on Fat Controllers and raw SQL, unit testing individual business logic functions is difficult. The most effective approach for the current architecture is **End-to-End (E2E) Browser Testing** (e.g., using Cypress, Laravel Dusk, or Playwright) combined with **Integration Tests** that hit the router.

## Critical Test Cases to Implement

### 1. Enrollment Integrity
- **Precondition:** Applicant has `approved` status.
- **Action:** Applicant selects conflicting sections.
- **Expected:** System blocks enrollment and returns error.

### 2. Assessment Calculation
- **Precondition:** Applicant enrolled in BSIT, 1st Year.
- **Action:** Generate assessment.
- **Expected:** Total matches exactly the defined `fee_templates` for BSIT 1st Year.

### 3. Scholarship Application
- **Precondition:** Assessment is 10,000 PHP.
- **Action:** Scholarship of "50% Tuition" is approved.
- **Expected:** Net Assessment is correctly reduced; total required payment reflects deduction.

### 4. Role Security (Regression)
- **Precondition:** Logged in as `applicant`.
- **Action:** Manually navigate to `/admin/admissions/review.php`.
- **Expected:** System forces redirect to `403 Forbidden` or login screen.
