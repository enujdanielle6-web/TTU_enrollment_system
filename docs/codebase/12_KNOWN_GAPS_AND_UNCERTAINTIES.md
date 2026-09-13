# 12. KNOWN GAPS AND UNCERTAINTIES

## Confirmed Critical Operational Defects
1. **LMS CSRF Deadlock:** Every state-changing form in LMS views omits `<input type="hidden" name="csrf_token">`, while `CsrfMiddleware` intercepts all POST requests. Result: HTTP 403 on all LMS POST actions.
2. **Download Controller Role Mismatch:** `DownloadController.php` checks `$_SESSION['role']`, which is never populated (auth uses `$_SESSION['user_role']`). Result: All material and assignment downloads return 403 Forbidden.
3. **Storage Path Escape:** `FacultyController::uploadMaterial` writes to `__DIR__ . '/../../../../storage/lms_materials/'`, escaping into `c:\xampp\storage\lms_materials/`. `DownloadController` reads from `app/uploads/lms/`. Result: Uploaded materials produce 404 Not Found.
4. **LMS Routes Lack Role Guards:** In `app/Routes/web.php`, student and faculty endpoints share a single group with only `AuthMiddleware`. Students can access faculty portals.
5. **SystemController Rejects Faculty Role:** `SystemController::processUser` whitelist rejects role `'faculty'`, preventing administrators from creating faculty accounts.
6. **Scheduler ↔ LMS Disconnect:** Timetables store instructor names as loose strings (`VARCHAR(150)`), failing to update `lms_courses.faculty_user_id`.
7. **Unsafe JIT Course Auto-Provisioning:** Read queries in enrollment repositories execute `INSERT INTO lms_courses` on missing courses, assigning them to lowest faculty ID or hardcoded ID 18.
8. **Horizontal Admin Privilege Escalation:** Administrative controllers omit `requirePermission()` calls, allowing staff in one office (e.g. cashier) to approve applications or edit curricula.

## Uncertainties & Environmental Dependencies
- Production web server MIME-sniffing configuration for direct uploads.
- External SMTP gateway deliverability for student credentials email.
