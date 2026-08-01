# Clinic Module

**Path**: `admin/clinic/`
**Role Required**: `clinic` or `superadmin`

The Clinic is a specialized verification checkpoint in the [[Enrollment Workflow]]. It operates in parallel with [[Modules/Admissions]].

## Core Responsibilities
1. **Medical Clearance**: Applicants must submit health records (e.g., Blood type, X-Ray results, Medical history) during registration.
2. **Review & Approval**: The Clinic staff reviews these records via `medical_process.php` and issues a "Medical Clearance".

## Data Flow
The clearance status is tracked in the `medical_records` table linked to the `user_id`. An applicant *cannot* proceed to [[Modules/Finance]] for fee assessment until both the Admissions and Clinic departments have approved their respective requirements.
