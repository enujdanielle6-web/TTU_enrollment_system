# Development Guide

## Environment Setup
1. **Server:** Install XAMPP. PHP must be at least version 7.4 (8.x recommended).
2. **Database:** 
   - Start Apache and MySQL in XAMPP.
   - Create a database named `sia`.
   - Import `database/schema.sql`.
   - Run any seeders located in `database/seed.sql`.
3. **Environment Variables:**
   - Copy or edit `.env`.
   - Ensure the database credentials match your local setup.
   - Configure PHPMailer SMTP details if testing email functionality.
4. **Dependencies:**
   - Ensure Composer is installed globally.
   - Run `composer install` in the project root to fetch PHPMailer.

## Debugging
- **PHP Errors:** Set `APP_ENV=development` in `.env`. The `public/index.php` front controller will print stack traces on 500 errors.
- **AJAX Debugging:** Check the browser's Network tab. XHR requests to endpoints in `App\Controllers\Api\` will show direct JSON responses.
- **Database Debugging:** Due to Fat Controllers, you will need to `var_dump()` or log the generated SQL strings directly inside the Controller methods before they are executed via PDO.

## Deployment Notes
- **Critical:** Never deploy with `APP_ENV=development`.
- Ensure Apache `.htaccess` overrides are enabled (`AllowOverride All` in `httpd.conf`) so that the router interception works.
