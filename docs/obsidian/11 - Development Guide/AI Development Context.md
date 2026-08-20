# AI Development Context & Engineering Guidelines

This document provides specialized architectural context, non-obvious design decisions, constraints, and sensitive trap areas to guide future AI coding agents working on the TTU Enrollment System and LMS.

---

## 1. Project Identity & Architectural Reality
- **System Name:** Triple T University (TTU) Enrollment System & Learning Management System (LMS).
- **Core Architecture:** **Hybrid MVC Monolith** built on **Vanilla PHP** (no Laravel, Symfony, or Yii).
- **Database Engine:** MariaDB 10.4+ / MySQL 8.0+ running 41 normalized relational tables in `sia`.
- **Frontend Stack:** Bootstrap 5.3, Vanilla CSS, jQuery 3.7+, HTML5.

---

## 2. Core Architectural Conventions

### 2.1 The "Fat Controller" Standard
- Controllers in `app/Controllers/` **own the business logic, input validation, raw PDO queries, and view rendering**.
- Models in `app/Models/` act strictly as basic data containers. **Do NOT build heavy ORM layers** or abstract SQL queries out of controllers unless creating dedicated Repositories or Services.

### 2.2 The "Application as Term" Concept
- **There is NO `students` table**.
- A student's identity resides in `users`.
- A student's enrollment record for an academic term is anchored to `applications.id`.
- Foreign keys for enrolled subjects (`college_enrollments`, `shs_enrollments`), clinic health records (`health_records`), and financial assessments (`student_assessments`) point to `application_id`, **not `user_id`**.

### 2.3 Dual Academic Tiers (College vs. SHS)
- The system supports both **College** and **Senior High School (SHS)** with parallel tables:
  - College: `college_programs`, `college_curricula`, `college_sections`, `college_enrollments`.
  - SHS: `shs_strands`, `shs_curricula`, `shs_sections`, `shs_enrollments`.
- When writing queries that touch enrollments or LMS courses, always check `applications.academic_level` or use the dedicated `CollegeEnrollmentRepository` and `ShsEnrollmentRepository`.

---

## 3. Critical Sensitive & High-Risk Areas

| Area | Why It Is Dangerous | Rule for AI Assistants |
|---|---|---|
| **Registration & OTP** | Modifying `AuthController::register` or `processVerifyEmail` can lock out new applicants or bypass email ownership checks. | Always preserve the 6-digit random generation, 15-minute expiry, `sendVerificationCodeEmail()` call, and session gating. |
| **Tuition Calculation** | `FinanceController` and `ApplicantController` compute dynamic tuition: $\text{Enrolled Units} \times \text{Rate per Unit}$. | Always check `fee_templates.is_per_unit` and join `subjects` for unit sums. Do not revert to hardcoded static totals. |
| **Clinic Clearance** | Health submission is an enforced prerequisite post-approval. | Never bypass `health_records` checks when modifying the enrollment pipeline. |
| **LMS Dual Provisioning**| LMS courses must auto-provision for both College and SHS. | Do not hardcode joins to `college_enrollments` alone; always maintain parity for `shs_enrollments`. |
| **Database Cascades** | Heavy use of `ON DELETE CASCADE` in MySQL. | Never run destructive `DELETE` statements on `users` or `applications` without understanding downstream cascading deletes. |
| **Modal Markup in Views**| Bootstrap 5 modals nested inside `<tbody>` freeze the browser screen with a black overlay. | Always place modal markup at the root of the document, outside of tables. |

---

## 4. Coding & Security Invariants
1. **Always Use Prepared PDO Statements:** Never concatenate variables into SQL strings. Use `:name` or `?` placeholders with `$stmt->execute([...])`.
2. **Always Escape View Output:** Use `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` or `esc($var)` when rendering user input into HTML views.
3. **Always Load `.env` Safely:** Ensure environment variables are loaded via `public/index.php` or `getenv()` with appropriate fallbacks.
4. **Preserve Legacy Script Paths:** Routes in `app/Routes/web.php` emulate legacy file paths (`/applicant/dashboard.php`, `/auth/login.php`) for backward compatibility. Do not remove `.php` extensions from defined routes without coordinating frontend links.

---

## 5. Standard Test Credentials for AI Verification

- **Superadmin:** `admin@ttu.edu.ph` / `admin123`
- **Admissions:** `admissions@ttu.edu.ph` / `admin123`
- **Finance/Cashier:** `cashier@ttu.edu.ph` / `admin123`
- **Clinic:** `clinic@ttu.edu.ph` / `admin123`
- **Enrolled Student:** `2026-000003` / `password123`
- **Faculty Instructor:** Faculty Employee ID / `password123`

---
**Related:**
- [[System Architecture]]
- [[Coding Standards]]
- [[Data Dictionary]]
- [[Business Rules]]
