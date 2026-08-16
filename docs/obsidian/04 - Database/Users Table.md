# Users Table

## Purpose
Stores the central identity and login credentials for all entities interacting with the system (applicants, students, faculty, and admins).

## Important Columns
- `id` (PK)
- `email` (Unique)
- `password` (Hashed)
- `role` ENUM: ('applicant','admin','superadmin','admissions','scholarship','cashier','clinic','faculty','scheduler')
- `student_number` (Nullable, unique)
- `college_curriculum_id` (Nullable FK to `college_curricula` to lock a user to a version)

## Relationships
- **Has Many:** `applications` (Historical enrollment records)
- **Has Many:** `activity_logs` (Audit trails)

## Security
- Controlled by `SessionSecurityMiddleware`. 
- `role` determines access to modules. See [[Security Overview]].
