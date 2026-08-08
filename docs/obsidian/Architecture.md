# System Architecture

The TTU Enrollment System is a modern web application built on a custom Vanilla PHP MVC (Model-View-Controller) architecture.

## Tech Stack
- **Backend**: Vanilla PHP (Object-Oriented, Custom MVC Framework)
- **Database**: MySQL / MariaDB (Accessed via raw PDO)
- **Frontend**: HTML5, Vanilla JavaScript, Bootstrap 5.3.3, Google Fonts (Outfit, Inter)
- **Icons**: Bootstrap Icons
- **Pattern**: Model-View-Controller (MVC)

## Design Pattern (MVC)
The system uses a custom MVC router to strictly separate concerns, enforcing maintainability and security.

1. **Routing (`app/Routes/web.php`)**: All HTTP requests are funneled through `public/index.php`, which instantiates the `Router` and dispatches requests based on defined URI maps.
2. **Middleware (`app/Middleware/`)**: Requests pass through a stack of Middleware (e.g., `SessionSecurityMiddleware`, `AuthMiddleware`, `RoleMiddleware`) to handle security, authentication, and role-based access control (RBAC).
3. **Controllers (`app/Controllers/`)**: Controllers extend `BaseController`. They handle input via `Request`, fetch data using Models/PDO, and return a `Response` (either JSON or HTML).
4. **Views (`app/Views/`)**: Display logic. Controllers use `$this->render()` to extract data into the view scope and output the final HTML.
5. **Models (`app/Models/`)**: Encapsulate business logic and database interactions.

## Core Directories
- `app/` - The MVC core. Contains `Controllers`, `Models`, `Views`, `Middleware`, `Routes`, and base `Core` classes.
- `public/` - The single entrypoint (`index.php`) and public assets (`css/`).
- `config/` - Environment settings and `$pdo` Database connection singleton.
- `database/` - Schema DDL and seed data.
- `images/`, `js/`, `uploads/` - Static assets and user-uploaded files.

## Security
- **Authentication**: Native PHP sessions (`$_SESSION`) secured by `SessionSecurityMiddleware`.
- **Authorization**: `RoleMiddleware` enforces RBAC down to specific controller actions.
- **CSRF**: Token validation required for all POST requests.
- **SQL Injection**: Prevented globally by strictly using PDO prepared statements (`$stmt->prepare()`).

## Missing Patterns (Technical Debt)
- No Object-Relational Mapping (ORM)
- No templating engine (e.g., Twig/Blade)
- No Dependency Injection Container (Dependencies are manually instantiated)
- No automated testing (Unit/Integration)

*Related:* [[Database Schema]], [[User Roles]]
