# MVC Strangler Fig Migration

## Overview
The system was originally built using standard procedural PHP where every URL mapped directly to a `.php` file (e.g., `/applicant/dashboard.php`). 

To modernize the codebase without breaking existing links or requiring a massive frontend rewrite all at once, the system employs the **Strangler Fig Pattern**.

## How It Works
1. **The `.htaccess` Firewall:** All requests that do not point to a physical file in `public/` are redirected to `public/index.php`. Direct access to physical `.php` files (except the front controller) is blocked.
2. **The Router (`app/Routes/web.php`):** The legacy URLs are registered as routes.
   ```php
   $router->get('/applicant/dashboard.php', ['App\Controllers\ApplicantController', 'dashboard']);
   ```
3. **The Interception:** When a user navigates to what they think is the old procedural file, the MVC router intercepts the request, runs it through modern [[Security Overview|Middleware]], and directs it to a modern Controller.

## Status
- `Partially Implemented`: The routing and views are fully migrated.
- `Missing`: The model layer is extremely thin. Controllers contain massive blocks of raw SQL, carrying over the procedural debt into the OOP structure.
- **Critical Risk:** See [[Known Issues]] regarding `AdmissionsController_clean.php` which is ~14MB in size.

## Related ADR
- [[ADR-002 Strangler Fig Migration]]
