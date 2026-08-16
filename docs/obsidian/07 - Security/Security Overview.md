# Security Overview

The TTU System's security relies primarily on framework middleware and Apache configuration.

## 1. Authentication & Sessions
- Managed via `App\Controllers\AuthController`.
- Sessions are strictly required for all internal routes.
- **Middleware:** `SessionSecurityMiddleware` and `AuthMiddleware` protect routes.

## 2. Authorization (Roles)
- Based on the `users.role` ENUM (`applicant`, `admin`, `cashier`, etc.).
- **Middleware:** `RoleMiddleware` checks the session's role against the required role for the route block.
- Example: `$router->group(['middleware' => ['...', 'App\Middleware\RoleMiddleware:admin']], ...)`

## 3. Web Server Firewall (`.htaccess`)
- Disables directory listing.
- Blocks direct URL access to `app/`, `config/`, and `database/`.
- Forces all non-file requests through the `public/index.php` front controller.
- Prevents direct execution of legacy `.php` scripts by redirecting them to the router.

## 4. SQL Injection Protection
- The system heavily uses `PDO` prepared statements inside the Fat Controllers. 
- *Risk:* Because raw SQL is prevalent, developers must be extremely diligent to *always* use parameter binding. A single concatenated variable in a Fat Controller could introduce a vulnerability.

## 5. Known Vulnerabilities & Risks
- **Fat Controllers:** The lack of strict Models increases the risk of inconsistent validation and SQL injection if coding standards slip.
- **Root Scripts:** Lingering files like `cleanup.php` or `patch_shs_sections.php` in the root directory could be hazardous if the `.htaccess` rules are ever bypassed or misconfigured.
