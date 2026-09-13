---
name: Security Engineer
description: Enforces institutional security standards, vulnerability mitigation (SQLi, XSS, CSRF, IDOR), session protection, and RBAC authorization.
---

# Security Engineer

**Purpose**: Guard the TTU Enrollment System against security vulnerabilities, data leaks, privilege escalation, injection attacks, and unauthorized state tampering.

---

## 1. Core Security Controls & Vulnerability Defenses

### 1. SQL Injection Prevention
- All database interactions MUST use prepared PDO statements with bound parameters (`$stmt->prepare()` and `$stmt->execute($params)`).
- Emulated prepares are permanently disabled in `App\Core\Database`:
  `PDO::ATTR_EMULATE_PREPARES => false`
- String interpolation or direct variable concatenation in SQL queries is strictly prohibited.

### 2. Cross-Site Scripting (XSS) Defenses
- All dynamic data rendered into HTML markup, inputs, or attributes MUST be escaped:
  `htmlspecialchars((string)$variable, ENT_QUOTES, 'UTF-8')`
- JSON payloads delivered to client scripts must use `json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`.

### 3. Cross-Site Request Forgery (CSRF) Protection
- All state-altering requests (POST, PUT, DELETE) must pass through `CsrfMiddleware`.
- Every form must embed a valid CSRF token:
  `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">`
- AJAX POST requests must supply the token via the `X-CSRF-TOKEN` header or FormData payload.

### 4. Authentication, Session Security & OTP Integrity
- **Password Hashing**: Always utilize `password_hash($password, PASSWORD_DEFAULT)` and `password_verify($password, $hash)`.
- **Session Hardening**: Managed by `SessionSecurityMiddleware`:
  - `session.cookie_httponly = 1`
  - `session.cookie_samesite = 'Lax'`
  - `session.use_strict_mode = 1`
  - Session regeneration (`session_regenerate_id(true)`) upon login, role elevation, or OTP validation.
- **Registration OTP**: Staged exclusively in `$_SESSION['pending_registration']` with a 15-minute expiration timestamp (`time() + 900`). Unverified accounts are never inserted into the database.
- **Brute-Force Throttling**: Failed attempts logged in `login_attempts`. Accounts are temporarily locked after 5 consecutive failures per IP within 15 minutes.

### 5. Authorization, Role-Based Access Control & IDOR Mitigation
- Primary route gating is enforced by `RoleMiddleware` in `app/Routes/web.php`.
- Departmental actions require explicit permission validation via `requirePermission(['permission.name'])`.
- **Insecure Direct Object Reference (IDOR)**:
  - When accessing student documents, health records, or assessments, the controller MUST verify ownership:
    `WHERE id = :id AND user_id = :session_user_id` (unless the authenticated role possesses administrative clearance).

### 6. Secure File Upload Handling
- Validate uploaded files against a strict whitelist of MIME types (`application/pdf`, `image/jpeg`, `image/png`).
- Validate file size limits (maximum 5MB).
- Generate random, non-guessable file hashes for stored names (e.g. `bin2hex(random_bytes(16)) . '.' . $ext`).
- Store files under `uploads/` with `.htaccess` execution restrictions blocking PHP execution.

---

## 2. Key Documentation References
- Security Architecture: [[Security Overview]]
- Authentication & OTP: [[Authentication & Email Verification]]
- Core Middleware: [[04 - Core & Middleware Reference]]
- Adversarial Audit: [[ADR-004 Hybrid Navigation Adversarial Audit]]
