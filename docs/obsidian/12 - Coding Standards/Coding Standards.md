# Coding Standards

## Current Codebase Reality
- **PHP:** Procedural/OOP mix. Controllers act as massive procedurals mapped to classes.
- **SQL:** Raw SQL queries via PDO inside controllers.
- **HTML/CSS:** Bootstrap 5 embedded inside PHP `app/Views/`.
- **JavaScript:** Primarily jQuery for DOM manipulation and AJAX.

## Recommended Standards

### PHP
- Follow PSR-12 coding standard.
- **DO NOT** add more raw SQL to controllers. Abstract new logic to Models or Service classes.
- Use strict typing `declare(strict_types=1);` in all new classes.

### SQL
- **ALWAYS** use prepared statements (`?` or `:name`).
- Never concatenate strings from `$_POST` or `$_GET` directly into queries.

### AJAX
- Endpoints should always return standardized JSON: `{"status": "success|error", "data": {...}, "message": "..."}`.
- Authenticate all AJAX calls using middleware.

### Views
- Keep PHP logic in views to an absolute minimum (only `if`, `foreach`, and `echo`).
- Use `htmlspecialchars()` when echoing any user-provided data.
