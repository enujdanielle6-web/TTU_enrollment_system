# ADR-006: Deferred Account Creation via OTP Verification

## Status
**Accepted**

## Date
2026-08-27

## Context
In the previous registration flow, submitting the registration form on `/auth/register.php` immediately inserted a row into the `users` database table with `email_verified = 0`. 

This design caused multiple operational and architectural issues:
1. **Database Pollution & Abandoned Records:** Users who typed an incorrect email or abandoned the OTP verification screen left unverified records occupying unique email addresses.
2. **Email Locking:** If a user made a typo or lost access to an email, they could not re-register with that email without administrator intervention.
3. **Security Surface:** Unverified accounts existed in the core authentication table, requiring defensive filtering across admissions, registrar, and reporting modules.

## Decision
We refactored the registration process to implement a **Deferred Account Creation** architecture:
1. When an applicant submits `/auth/register.php`, all validations (unique email, password length/confirmation, required fields) are processed in-memory.
2. If validation succeeds, credentials and the 6-digit OTP are staged in server-side session memory (`$_SESSION['pending_registration']`).
3. **No database insertion occurs** at registration time.
4. When the applicant enters and verifies the valid 6-digit OTP on `/auth/verify_email.php`, the controller (`AuthController@processVerifyEmail`) inserts the user into `users` with `email_verified = 1` and logs them in immediately.

## Consequences

### Positive
- **Zero Database Pollution:** Only applicants who prove ownership of a valid email inbox exist in the `users` table.
- **Immediate Clean Re-Registration:** If an applicant leaves or abandons registration before verifying, the email remains unlocked and available.
- **Simpler Business Logic:** Downstream systems (Admissions, LMS, Registrar) can trust that every record in `users` represents a verified entity.

### Negative / Mitigations
- Session dependency during registration: If the applicant closes their browser session before entering the OTP, they must restart registration. This is standard behavior across modern web services (e.g. Google, GitHub) and is explicitly documented.

---
**Related:**
- [[Applicant Registration Workflow]]
- [[Authentication & Email Verification]]
- [[Users Table]]
