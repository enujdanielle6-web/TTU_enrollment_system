# Workflow Index

This directory documents the end-to-end operational workflows of the TTU Enrollment System and LMS.

---

## Master Workflow Directory

### 1. [[Student Lifecycle Workflow]]
* **Overview:** The overarching student progression across all departments: Registration $\rightarrow$ Email Verification $\rightarrow$ Application $\rightarrow$ Document Review $\rightarrow$ Health Clearance $\rightarrow$ Admissions Approval $\rightarrow$ Section Selection $\rightarrow$ Assessment $\rightarrow$ Payment $\rightarrow$ Credential Dispatch $\rightarrow$ LMS Access.

### 2. [[Applicant Registration Workflow]]
* **Overview:** Account creation on `/auth/register.php`, 6-digit OTP generation, PHPMailer SMTP transmission, verification gating on `/auth/verify_email.php`, and session authentication.

### 3. [[Health Submission & Clearance Workflow]]
* **Overview:** Post-approval health declaration submission via `/applicant/health_info.php`, clinic evaluation via `/admin/clinic/medical_clearance.php`, and clearance gating rules.

### 4. [[Payment & Assessment Workflow]]
* **Overview:** Dynamic tuition rate-per-unit evaluation, scholarship deduction, online bank proof of payment upload, cashier verification, and official receipt issuance.

### 5. [[Document Submission Preference Workflow]]
* **Overview:** Dual verification pathway (Online Upload vs On-Campus Physical Submission), interactive secondary confirmation lock, dynamic guide, and status synchronization.

---
**Related:**
- [[Module Index]]
- [[Business Rules]]
- [[System Architecture]]
- [[National Service Training Program (NSTP) Architecture]]
