# Online Enrollment System

## Project Vision

This project is a complete web-based Online Enrollment System built for schools that want to digitize the enrollment process.

The system must simulate a real-world enrollment workflow used by educational institutions.

The project must prioritize:

- Simplicity
- Reliability
- Maintainability
- Security
- Scalability

The project is intended to run locally using XAMPP and later be deployable to a standard PHP hosting environment without major modifications.

---

# Technology Stack

Backend
- PHP (Procedural)

Database
- MySQL

Frontend
- PHP-rendered pages
- CSS
- jQuery
- AJAX

Server
- Apache (XAMPP)

---

# Development Principles

The project should be modular.

Every feature must integrate into the existing project.

Avoid duplicate code.

Avoid unnecessary libraries.

Prefer reusable components.

The application should remain beginner-friendly while following professional coding practices.

---

# Enrollment Workflow

Applicant opens the enrollment page.

↓

Creates an application.

↓

Fills in personal information.

↓

Uploads required documents (if enabled).

↓

Submits application.

↓

Application status becomes "Pending".

↓

Administrator reviews the application.

↓

Administrator may:

- Approve
- Reject
- Request Correction

↓

Applicant can monitor application status.

↓

Approved applicants become officially enrolled students.

---

# User Roles

## Applicant

Can:

- Register
- Login
- Submit application
- Edit application before approval
- View application status
- Upload documents
- Print acknowledgement receipt (future feature)

Cannot:

- Access admin pages
- Modify approved records

---

## Administrator

Can:

- Login
- Review applicants
- Search records
- Approve applications
- Reject applications
- Request corrections
- Manage school settings
- Manage users
- View reports

---

# Application Status

Pending

Under Review

Correction Required

Approved

Rejected

Enrolled

---

# Coding Standards

Variable names should be meaningful.

Functions should perform one responsibility.

Keep files organized.

Use prepared statements.

Validate server-side.

Never trust client-side validation.

Escape HTML output.

---

# AJAX Guidelines

Use AJAX whenever practical for:

- Login
- Registration
- Enrollment submission
- Status updates
- Table refreshes

Avoid unnecessary page reloads.

---

# Future Modules

Student Management

Course Management

Academic Year

Grade Level

Sections

Reports

Announcements

Notifications

Email Support

PDF Generation

Audit Logs

Backup & Restore

Settings

---

# Design Philosophy

Clean

Professional

Responsive

Minimal

Fast

Easy to navigate

Consistency is more important than decoration.

---

# Goal

The finished project should resemble a real enrollment management system used by schools rather than a classroom exercise.