# Triple T University (TTU) Enrollment System Documentation

Welcome to the technical source of truth for the **Triple T University (TTU) Enrollment & Learning Management System**.

This repository documentation maps out how the codebase, database, authentication pipeline, and academic business rules interact to support the complete student lifecycle.

---

## Master Documentation Index

### 1. Architecture & Security
- **[[System Architecture]]**: High-level overview of the backend, request lifecycle, and layer boundaries.
- **[[MVC Strangler Fig Migration]]**: Strategy for migrating legacy procedural scripts to the unified Hybrid MVC.
- **[[System Architecture Reconnaissance]]**: Deep-dive architectural report and dependency mapping.
- **[[Security Overview]]**: Middleware, authorization, firewall (`.htaccess`), and PDO SQL injection defenses.
- **[[Authentication & Email Verification]]**: 6-digit OTP registration gating, session security, and multi-portal login.
- **[[Email & Notification System]]**: PHPMailer, Google SMTP integration, verification and credentials emails.

### 2. Core Modules
- **[[Module Index]]**: Master catalog of all administrative, applicant, and academic modules.
- **[[Applicant Portal]]**: Online registration, document upload, health info submission, enrollment, and status tracking.
- **[[Admissions]]**: Application review, document verification, approval/rejection, and automated credential dispatch.
- **[[Clinic]]**: Health information evaluation, medical clearances, and applicant clearance gating.
- **[[Registrar]]**: Curriculum architecture, College programs, SHS strands, subjects, and student academic records.
- **[[Scheduler]]**: Section management, timetable matrix, faculty advisers, and room assignments.
- **[[Finance]]**: Fee templates, dynamic tuition rate-per-unit assessments, cashier verification, and receipts.
- **[[Scholarship]]**: Financial aid programs, application evaluations, and discount deductions.
- **[[LMS]]**: Learning Management System for College & SHS students and faculty (courses, modules, assignments, quizzes, gradebook, attendance).

### 3. Workflows
- **[[Workflow Index]]**: Directory of core business workflows.
- **[[Student Lifecycle Workflow]]**: End-to-end journey from applicant registration to enrollment and LMS access.
- **[[Applicant Registration Workflow]]**: Registration, 6-digit OTP email generation, and verification.
- **[[Health Submission & Clearance Workflow]]**: Medical clearance submission and clinic review.
- **[[Payment & Assessment Workflow]]**: Assessment math, bank proof upload, and cashier confirmation.

### 4. Database & Curriculum
- **[[Database Overview]]**: The "Application as Term" concept and relational architecture.
- **[[Data Dictionary]]**: Complete technical specifications for all 41 database tables.
- **[[Curriculum Architecture]]**: College programs vs. SHS strands, versioning, and curriculum immutability.
- **[[Users Table]]**: Identity root, roles enum, and OTP verification columns.
- **[[Applications Table]]**: Lifecycle anchor and student enrollment state.
- **[[Application Documents Table]]**: Requirement uploads and verification states.

### 5. API & Standards
- **[[API Documentation]]**: Complete reference for all internal JSON AJAX endpoints.
- **[[Business Rules]]**: Comprehensive catalog of confirmed and enforced business rules.
- **[[Coding Standards]]**: PSR-12, Fat Controller guidelines, and security requirements.
- **[[Testing Strategy]]**: Manual testing matrix, test scenarios, and planned automation.
- **[[Known Issues]]**: Architectural debt and resolved anomaly logs.

### 6. Development & Operations
- **[[Project Structure & Code Map]]**: Directory map, component placement rules, and file references.
- **[[AI Development Context]]**: Critical context, rules, and sensitive areas for AI coding assistants.
- **[[Development Guide]]**: Local environment setup, XAMPP, `.env` configuration, and debugging.
- **[[Installation & Setup Guide]]**: Step-by-step installation instructions.
- **[[Troubleshooting Runbook]]**: Common runtime error resolutions, SMTP debugging, and recovery.

### 7. Architectural Decision Records (ADRs)
- **[[ADR Index]]**: Architectural decision log index.
- **[[ADR-001 The Application as Term Concept]]**: Why there is no central `students` table.
- **[[ADR-002 Strangler Fig Migration]]**: Incremental MVC modernization strategy.
- **[[ADR-003 Hybrid SPA Navigation Design]]**: Dynamic HTML fragment swapping in the LMS.
- **[[ADR-004 Hybrid Navigation Adversarial Audit]]**: Security analysis of SPA navigation.

---

## High-Level System Summary
The TTU system manages both **College** and **Senior High School (SHS)** students. It is a monolithic **Hybrid MVC** Vanilla PHP web application using MariaDB/MySQL, Bootstrap 5, jQuery, and PHPMailer. The database acts as the single source of truth across administrative, applicant, and LMS domains.
