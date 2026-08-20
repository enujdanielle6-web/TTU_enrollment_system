# Coding Standards & Guidelines

This document defines the architectural conventions, PHP coding standards, security requirements, and data access rules for the TTU Enrollment System.

---

## 1. Core Architectural Standards (Hybrid MVC)
- **Fat Controllers:** In accordance with the system's Hybrid MVC pattern, controllers are the primary orchestrators of business logic, request validation, raw PDO SQL queries, and view rendering.
- **Cross-Tier Separation:** Avoid hardcoding College-only queries where SHS is also supported. Utilize the `CollegeEnrollmentRepository` and `ShsEnrollmentRepository` abstractions.
- **Service Layer Usage:** Aggregate metrics, streaks, deadlines, and multi-entity workflows belong in `app/Services/` (e.g. `LmsService`).

---

## 2. PHP Standards (PSR-12 & Vanilla PHP)
- Follow **PSR-12** formatting conventions (4 spaces indentation, camelCase methods, PascalCase classes).
- Use strict typing where feasible (`declare(strict_types=1);`).
- Do not introduce heavyweight external frameworks. Maintain native Vanilla PHP independence.

---

## 3. Database & SQL Standards
- **MANDATORY: Parameterized Prepared Statements:**
  ```php
  // CORRECT:
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND is_active = :status");
  $stmt->execute(['email' => $email, 'status' => 1]);

  // STRICTLY PROHIBITED:
  $stmt = $pdo->query("SELECT * FROM users WHERE email = '$email'"); // Vulnerable to SQL Injection!
  ```
- **Transaction Safety:** Wrap multi-table financial or enrollment operations in transactions:
  ```php
  $pdo->beginTransaction();
  try {
      // Multiple statements...
      $pdo->commit();
  } catch (\Throwable $e) {
      $pdo->rollBack();
      throw $e;
  }
  ```

---

## 4. Frontend & View Standards
- **HTML Escaping:** Always escape dynamic user data rendered into views to prevent Cross-Site Scripting (XSS):
  ```php
  <?= htmlspecialchars($userData['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
  // or use the helper:
  <?= esc($userData['first_name']); ?>
  ```
- **Bootstrap Component Placement:** Modals must never be nested inside table bodies (`<tbody>`), as nested backdrops break z-index calculations in Bootstrap 5. Place modal dialog markups at the root of the document container.

---

## 5. Security & Session Standards
- Always regenerate session IDs upon authentication state transitions (`session_regenerate_id(true)`).
- Validate CSRF tokens on all POST requests using `CsrfMiddleware` or `verifyCsrfToken()`.
- Guard all administrative routes with `RoleMiddleware`.

---
**Related:**
- [[System Architecture]]
- [[Security Overview]]
- [[AI Development Context]]
