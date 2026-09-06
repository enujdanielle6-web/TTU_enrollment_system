# Core Framework & Middleware Reference Manual

This document provides complete, verified file-level documentation for all **6 Core Framework classes** (`app/Core/`) and all **6 Middleware interceptors** (`app/Middleware/`).

---

## 1. Core Framework Classes (`app/Core/`)

### `Router.php`
- **File:** `Router.php`
- **Path:** `app/Core/Router.php`
- **Module:** Framework Engine
- **Purpose:** Central HTTP routing engine matching incoming URIs and request methods to controller actions, applying middleware pipelines, and resolving view renders.
- **Responsibilities:**
  - Registers HTTP routes: `get()`, `post()`, `put()`, `delete()`.
  - Supports route prefixes and grouped middleware inheritance (`group()`).
  - Executes middleware chains sequentially before invoking the destination controller closure or method.
  - Matches dynamic URI parameters (e.g. `/lms/student/course/{course_id}/assignments/{id}`).
  - Provides fallback view rendering (`renderView()`).
- **Key Methods:**
  - `get(string $path, callable|array $callback): self` — Registers GET route.
  - `post(string $path, callable|array $callback): self` — Registers POST route.
  - `group(array $attributes, callable $callback): void` — Creates nested route group with shared prefixes or middleware.
  - `resolve(): mixed` — Resolves the current HTTP request through the middleware pipeline to the destination action.
  - `renderView(string $view, array $data = []): string` — Isolates data scope and buffers view output.
- **Dependencies:** `App\Core\Request`, `App\Core\Response`, `App\Core\HttpException`
- **Used By:** `public/index.php`, `app/Routes/web.php`
- **Related Documentation:** [[System Architecture]], [[MVC Strangler Fig Migration]]

---

### `Request.php`
- **File:** `Request.php`
- **Path:** `app/Core/Request.php`
- **Module:** Framework Engine
- **Purpose:** HTTP request abstraction providing sanitized access to superglobals (`$_GET`, `$_POST`, `$_SERVER`, headers).
- **Responsibilities:**
  - Parses HTTP method (handling `_method` override for PUT/DELETE).
  - Sanitizes and extracts URI path and query strings.
  - Provides helper methods: `isPost()`, `isAjax()`, `input()`, `query()`, `post()`, `all()`.
- **Key Methods:**
  - `getMethod(): string` — Returns uppercase HTTP method (`GET`, `POST`, etc.).
  - `getUri(): string` — Returns sanitized URI path.
  - `input(string $key, mixed $default = null): mixed` — Retrieves item from combined POST/GET payload.
  - `isAjax(): bool` — Checks for `X-Requested-With: XMLHttpRequest` header.
- **Used By:** Passed into every controller action by `Router.php`.

---

### `Response.php`
- **File:** `Response.php`
- **Path:** `app/Core/Response.php`
- **Module:** Framework Engine
- **Purpose:** HTTP response helper managing status codes, JSON serialization, and redirection.
- **Responsibilities:**
  - Sets HTTP status codes (`setStatusCode`).
  - Sets `Content-Type: application/json` and serializes data payloads (`json`).
  - Dispatches HTTP 302 location redirects (`redirect`).
- **Key Methods:**
  - `setStatusCode(int $code): self` — Sets HTTP response code (200, 404, 500, etc.).
  - `json(array $data, int $status = 200): void` — Serializes JSON and exits cleanly.
  - `redirect(string $url): void` — Sends redirect header and terminates execution.
- **Used By:** Passed into every controller action by `Router.php`.

---

### `Database.php`
- **File:** `Database.php`
- **Path:** `app/Core/Database.php`
- **Module:** Persistence & Infrastructure
- **Purpose:** Singleton database connection manager providing configured PDO instances for MariaDB.
- **Responsibilities:**
  - Reads connection credentials from environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`).
  - Configures secure PDO attributes:
    - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` (throws on SQL errors)
    - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` (returns associative arrays)
    - `PDO::ATTR_EMULATE_PREPARES => false` (native prepared statements to prevent SQL injection)
- **Key Methods:**
  - `static getConnection(): PDO` — Returns active singleton PDO connection instance.
- **Used By:** All controllers, models, and domain services across the entire repository.
- **Related Documentation:** [[Database Overview]], [[Security Overview]]

---

### `BaseController.php`
- **File:** `BaseController.php`
- **Path:** `app/Core/BaseController.php`
- **Module:** Framework Engine
- **Purpose:** Abstract foundational controller class inherited by all application controllers.
- **Used By:** Extended by all 38 controller classes in `app/Controllers/`.

---

### `HttpException.php`
- **File:** `HttpException.php`
- **Path:** `app/Core/HttpException.php`
- **Module:** Error Handling
- **Purpose:** Specialized exception class carrying HTTP status codes (e.g. 403 Forbidden, 404 Not Found).
- **Responsibilities:**
  - Allows controllers or middleware to throw structured HTTP error responses caught cleanly by `public/index.php`.
- **Key Methods:**
  - `getStatusCode(): int` — Returns HTTP integer status code.
- **Used By:** `Router.php`, `AuthMiddleware.php`, `RoleMiddleware.php`.

---

## 2. Middleware Pipeline Interceptors (`app/Middleware/`)

### `MiddlewareInterface.php`
- **File:** `MiddlewareInterface.php`
- **Path:** `app/Middleware/MiddlewareInterface.php`
- **Module:** Middleware Pipeline
- **Purpose:** Contract defining the standard middleware invocation signature.
- **Key Methods:**
  - `handle(Request $request, Response $response, Closure $next): mixed` — Intercepts request, executes pre-checks, and yields to `$next()`.

---

### `SessionSecurityMiddleware.php`
- **File:** `SessionSecurityMiddleware.php`
- **Path:** `app/Middleware/SessionSecurityMiddleware.php`
- **Module:** Security & Session Integrity
- **Purpose:** Core security interceptor guarding against session fixation, session hijacking, stale sessions, and enforcing mandatory password changes.
- **Responsibilities:**
  - Enforces secure session cookie settings (`HttpOnly`, `SameSite=Lax`).
  - Periodically regenerates session IDs to prevent session fixation attacks.
  - Enforces session inactivity timeout (default: 30 minutes).
  - Enforces **Force Password Reset Gate:** If authenticated student has `force_password_reset = 1` in session/database, intercepts all requests and redirects directly to password change view until updated.
- **Key Methods:**
  - `handle(Request $request, Response $response, Closure $next): mixed`
- **Related Documentation:** [[Security Overview]], [[Authentication & Email Verification]]

---

### `CsrfMiddleware.php`
- **File:** `CsrfMiddleware.php`
- **Path:** `app/Middleware/CsrfMiddleware.php`
- **Module:** Security & Anti-CSRF
- **Purpose:** Defends mutating HTTP routes against Cross-Site Request Forgery.
- **Responsibilities:**
  - Automatically initializes cryptographically secure 32-byte CSRF token in session (`$_SESSION['csrf_token']`).
  - Verifies submitted `csrf_token` on all `POST`, `PUT`, `DELETE` requests.
  - Rejects invalid tokens with HTTP 403 Forbidden.
- **Key Methods:**
  - `handle(Request $request, Response $response, Closure $next): mixed`
- **Related Documentation:** [[Security Overview]]

---

### `AuthMiddleware.php`
- **File:** `AuthMiddleware.php`
- **Path:** `app/Middleware/AuthMiddleware.php`
- **Module:** Security & Authentication
- **Purpose:** Ensures caller possesses an active, authenticated session.
- **Responsibilities:**
  - Checks if `$_SESSION['user_id']` is present and non-empty.
  - Redirects unauthenticated requests to `/auth/login.php` with flash error message.
- **Key Methods:**
  - `handle(Request $request, Response $response, Closure $next): mixed`

---

### `RoleMiddleware.php`
- **File:** `RoleMiddleware.php`
- **Path:** `app/Middleware/RoleMiddleware.php`
- **Module:** Security & Role-Based Access Control (RBAC)
- **Purpose:** Restricts route access to specific authorized user roles.
- **Responsibilities:**
  - Evaluates session `$_SESSION['role']` against permitted role array (e.g. `RoleMiddleware:admin,superadmin`).
  - Always permits `superadmin` role override.
  - Rejects unauthorized users with HTTP 403 Forbidden.
- **Key Methods:**
  - `__construct(string ...$roles)` — Accepts permitted role list.
  - `handle(Request $request, Response $response, Closure $next): mixed`
- **Related Documentation:** [[Security Overview]], [[System Administration]]

---

### `TestMiddleware.php`
- **File:** `TestMiddleware.php`
- **Path:** `app/Middleware/TestMiddleware.php`
- **Module:** Testing & Diagnostic
- **Purpose:** Diagnostic middleware used for testing route groups and parameter passing.

---
**Related:**
- [[00 - File Reference Index]]
- [[01 - Controllers Reference]]
- [[Security Overview]]
- [[System Architecture]]
