-- ============================================================================
-- Triple T University Enrollment & LMS - Create the FIRST Superadmin account
-- Run THIRD (after 01_schema.sql and 02_reference_data.sql), in phpMyAdmin > SQL tab.
--
-- There is no default password. Before running:
--   1. Generate a password hash ON YOUR OWN PC (XAMPP), then type your password and press Enter:
--        C:\xampp\php\php.exe -r "echo password_hash(trim(fgets(STDIN)), PASSWORD_DEFAULT), PHP_EOL;"
--   2. Replace the placeholders below:
--        CHANGE_ME_FIRST / CHANGE_ME_LAST   your name
--        admin@CHANGE_ME.example            the email you will log in with
--        PASTE_BCRYPT_HASH_HERE             the hash from step 1 (starts with $2y$) - it appears TWICE,
--                                           use Find & Replace so both are replaced
--   3. Paste this whole file into phpMyAdmin > SQL and click "Go".
--
-- Safety: nothing is inserted unless a real bcrypt hash was pasted (WHERE clause below).
-- Log in afterwards at:  https://YOUR-DOMAIN/auth/login.php
-- Never save the edited copy of this file in Git.
-- ============================================================================

INSERT INTO `users`
    (`first_name`, `last_name`, `email`, `ttu_email`, `password`, `student_number`,
     `role`, `department`, `permissions`, `email_verified`, `is_active`)
SELECT
    'CHANGE_ME_FIRST', 'CHANGE_ME_LAST', 'admin@CHANGE_ME.example', 'admin@CHANGE_ME.example',
    'PASTE_BCRYPT_HASH_HERE', NULL,
    'superadmin', 'System Administration', '["*"]', 1, 1
FROM DUAL
WHERE 'PASTE_BCRYPT_HASH_HERE' LIKE '$2y$%';

-- Should return exactly 1 row. If it returns 0 rows, the hash placeholder was not replaced.
SELECT `id`, `email`, `role`, `is_active` FROM `users` WHERE `role` = 'superadmin';
