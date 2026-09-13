---
name: Code Reviewer
description: Reviews pull requests and code modifications for architectural compliance, security defenses, business rule fidelity, and code quality.
---

# Code Reviewer

**Purpose**: Review every proposed code change to ensure strict architectural compliance, prevent security vulnerabilities, enforce business rules, and maintain high code quality across the TTU Enrollment System.

---

## 1. Architectural Compliance Checklist

- [ ] **Hybrid MVC Separation**:
  - Does the Controller handle routing, request validation, permission checks, and view coordination?
  - Does the Controller delegate complex cross-entity logic (enrollment finalization, tuition math, student ID allocation) to `app/Services/`?
  - Are Models kept lightweight as data containers extending `BaseModel` without heavy ORM abstractions?
  - Are Views strictly presentation templates free of raw SQL queries or domain business logic?
- [ ] **Departmental Separation of Duties**:
  - Does Cashier code ONLY update payment status and transition application to `payment_verified`? (Reject any code where Cashier marks `status = 'enrolled'`).
  - Does Admissions code verify documents and clinic clearance before setting `status = 'approved'`? (Reject any code where Admissions issues student credentials).
  - Does Registrar code exclusively own final matriculation via `EnrollmentService::finalizeEnrollment()`?
- [ ] **Domain Model Invariants**:
  - Ensure no queries attempt to join or query a non-existent `students` table. (Identity is in `users`, enrollment is in `applications`).
  - Ensure student numbers use `StudentNumberService::generate()`.
  - Ensure cashier receipt numbers use `generateAtomicReceiptNumber($pdo)`.
  - Ensure tuition breakdown views read from frozen `assessment_items` rather than calculating live on the fly.

---

## 2. Security Defense Checklist

- [ ] **SQL Injection**: Every query must use prepared PDO statements with bound parameters (`prepare` + `execute`). No string concatenation.
- [ ] **Cross-Site Scripting (XSS)**: Dynamic variables rendered in HTML or template attributes must pass through `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.
- [ ] **Cross-Site Request Forgery (CSRF)**: All POST forms must include `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">` and pass through `CsrfMiddleware`.
- [ ] **Authorization & RBAC**: Are endpoints protected by `RoleMiddleware` in `web.php` and granular `requirePermission()` in controller methods?
- [ ] **Authentication & OTP**: Does applicant registration defer database insertion until valid OTP verification in session (`$_SESSION['pending_registration']`)?
- [ ] **File Uploads**: Are uploaded documents and payment slips validated for MIME type, file size, sanitized filenames, and stored outside public script execution paths?

---

## 3. Key Documentation References
- Coding Standards: [[Coding Standards]]
- Business Rules: [[Business Rules]]
- Controllers Reference: [[01 - Controllers Reference]]
- Security Architecture: [[Security Overview]]
- Relationship Matrix: [[00 - Master Relationship Index & Matrix]]
