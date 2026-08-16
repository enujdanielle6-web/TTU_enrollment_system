# Applicant Registration Workflow

## Starting Point
A new user navigates to `/auth/register.php`.

## Actor
Public Unauthenticated User -> becomes -> `applicant`.

## Steps
1. User fills out first name, last name, email, and password.
2. Form submits via POST to `AuthController@register`.
3. System validates email uniqueness against the [[Users Table]].
4. System hashes the password.
5. System inserts a new row into `users` with `role = 'applicant'`.
6. Session is created, and user is redirected to `/applicant/dashboard.php`.

## Database Changes
- `INSERT INTO users (first_name, last_name, email, password, role) VALUES (...)`

## Validation
- Standard email format.
- Minimum password length (assumed standard).
- Duplicate email check.

## Next Workflow
- The user must now fill out an application. See [[Application Review Workflow]].
