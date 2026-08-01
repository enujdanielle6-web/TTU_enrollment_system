# System Architecture

The TTU Enrollment System is a traditional, server-rendered web application built entirely on a vanilla PHP stack.

## Tech Stack
- **Backend**: Vanilla PHP (No framework, no Composer, no PSR-4 autoloader)
- **Database**: MySQL / MariaDB (Accessed via raw PDO)
- **Frontend**: HTML5, Vanilla JavaScript, Bootstrap 5.3.3, Google Fonts (Poppins)
- **Icons**: Bootstrap Icons
- **Pattern**: Page-Controller Pattern

## Design Pattern (Page-Controller)
Instead of an MVC framework, the system uses a flat **Page-Controller** pattern. Each PHP file in the repository acts as both the controller and the view for a specific page.

A typical file (e.g., `admin/admissions/application_process.php`) follows this structure:
1. Include Authentication (`includes/auth.php`) to block unauthorized access.
2. Include Database (`config/database.php`) to instantiate `$pdo`.
3. Handle `POST` requests and execute inline SQL via PDO.
4. Fetch data via `SELECT` queries for rendering.
5. Include Header (`includes/header.php`) and Navbar (`admin/components/navbar.php`).
6. Output raw HTML with inline PHP `echo` statements for dynamic data.
7. Include Footer (`includes/footer.php`).

## Core Directories
- `admin/` - Departmental portals (Admissions, Clinic, Finance, Registrar, Scholarship, System).
- `applicant/` - Applicant portal for registration, enrollment, and payment.
- `auth/` - Login, logout, and session establishment.
- `config/` - Environment settings and `$pdo` instantiation.
- `database/` - Schema DDL, seed data, and obsolete migrations.
- `includes/` - Reusable HTML partials, helper functions, and auth middleware.
- `assets/`, `css/`, `js/`, `images/`, `uploads/` - Static files and user uploads.

## Security
- **Authentication**: Native PHP sessions (`$_SESSION`).
- **CSRF**: Every form submission requires a valid CSRF token generated in `includes/functions.php`.
- **SQL Injection**: Prevented globally by strictly using PDO prepared statements (`$stmt->prepare()`).

## Missing Patterns (Technical Debt)
- No Object-Relational Mapping (ORM)
- No templating engine (e.g., Twig/Blade)
- No dependency injection (Global `$pdo` is used)
- No automated testing (Unit/Integration)

*Related:* [[Database Schema]], [[User Roles]]
