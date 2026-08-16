# TTU Enrollment System Documentation

Welcome to the technical source of truth for the **Triple T University (TTU) Enrollment System**. 

This Obsidian vault contains the reverse-engineered documentation of the current hybrid-MVC system. It maps out how the codebase, database, and business rules interact to support the complete student lifecycle.

## Navigation

- **[[System Architecture]]**: High-level overview of the backend, frontend, and the [[MVC Strangler Fig Migration]].
- **[[Module Index]]**: Documentation for every distinct module (Applicant, Admissions, Registrar, Finance, etc.).
- **[[Workflow Index]]**: Step-by-step guides for core processes like [[Applicant Registration Workflow]] and [[Enrollment Workflow]].
- **[[Database Overview]]**: ERD details, relationships, and the unique [[Applications Table]] concept.
- **[[Curriculum Architecture]]**: How college programs, SHS strands, and versioning work.
- **[[Business Rules]]**: Confirmed and implied system rules.
- **[[Security Overview]]**: Middleware, authentication, and role boundaries.
- **[[API Documentation]]**: Internal AJAX endpoints powering the UI.
- **[[Known Issues]]**: Critical bugs, including the 14MB fat controller anomaly.
- **[[Development Guide]]**: How to run and debug the system.
- **[[ADR Index]]**: Architectural Decision Records explaining *why* the system is built this way.

## High-Level System Overview
The TTU system manages both **College** and **Senior High School (SHS)** enrollments. It is currently mid-migration from procedural PHP to a custom MVC architecture. It uses MariaDB, standard web frontends (Bootstrap/jQuery), and a unified `users` table to manage all actors.

> **Developer Note:**
> Always refer to the [[Known Issues]] and [[System Architecture]] before refactoring. The system uses "Fat Controllers" and lacks a robust ORM, meaning database schemas are tightly coupled to controller logic.
