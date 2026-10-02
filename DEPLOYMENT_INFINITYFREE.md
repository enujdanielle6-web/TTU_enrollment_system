# Deploying to InfinityFree (free hosting)

A step-by-step guide for putting the **Triple T University Enrollment & LMS** system online on
InfinityFree's free plan. Follow the sections in order. Nothing here needs SSH or a terminal on the server.

> **Never paste passwords into chat, GitHub, or this document.** You will only type them into
> `config/config.php` on your own computer and into InfinityFree's own control panel.

---

## 0. What works and what does not on the free plan

| Feature | Status on InfinityFree free | Notes |
|---|---|---|
| PHP app, MySQL, logins, roles, enrollment, LMS, uploads | ✅ Works | Tested locally in a domain-root setup (see §11). |
| Sending email (verification codes, password reset, credentials) | ✅ Expected to work | Must use an external SMTP server on **port 587** (Gmail App Password). PHP `mail()` is not used. *Verify live.* |
| PayMongo online checkout | ⚠️ Partly | Outgoing API calls work. Payment is confirmed when the student is **redirected back** to the site after paying. |
| PayMongo **webhooks** (`/api/webhooks/paymongo`) | ❌ Blocked | InfinityFree's browser-security check blocks all non-browser requests. If a student closes the tab before returning, the payment stays *pending* until that student opens the **Assessment** page again (it re-checks PayMongo) or a cashier uses the PayMongo *reconcile* action on the cashier payments page. Fix: premium/other hosting. |
| Quiz generator reading .pdf / .docx / .pptx materials | ✅ Expected to work | PDFs are read by `smalot/pdfparser`, which ships inside `vendor/` (no Composer on the server). .docx/.pptx need PHP's `zip` extension, which InfinityFree normally has enabled. Scanned/image-only PDFs have no text and are skipped. *Verify live* (see §8). |
| Uploads larger than **10 MB** | ❌ Blocked | Hard file-size limit. Applicant/payment uploads are capped at 5 MB already; LMS assignment uploads allow 25 MB in the app but files over 10 MB will fail. |
| SQL views (`faculty_workloads_view`, `student_academic_records_view`) | ❌ Not allowed | Not used by the app; they are left out of the import files. |
| Cron jobs / background workers | ❌ Disabled | Not needed — session expiry and clean-ups run during normal page requests. |
| `setup_database.php` (drops & rebuilds the DB) | 🚫 Intentionally disabled | Not uploaded, and refuses to run when `APP_ENV=production`. |
| Hit limits | ⚠️ Watch | The *Payment Monitoring* page and the *Payment Queue* page poll the server every 3 s. Leaving them open for hours uses up the free plan's daily hit allowance — close them when not needed. |

---

## 1. Prepare the files on your PC (once per deployment)

There is **no Composer or npm build step**: `vendor/` (PHPMailer, and `smalot/pdfparser` for reading PDFs in the quiz generator) and all CSS/JS libraries are committed to the project.
If you add or update a Composer package, run `composer install` locally and commit the changed `vendor/` folder together with `composer.json`/`composer.lock`, because the server cannot run Composer.

1. Open a terminal in the project folder (`C:\xampp\htdocs\sia`) and run:
   ```bash
   C:\xampp\php\php.exe scripts/build_infinityfree_package.php
   ```
   This creates the clean upload folder **`dist\infinityfree\htdocs\`** (about 415 files / 16 MB).
   It contains only what the server needs — **no** `.env`, SQL dumps, docs, test scripts, or anyone's uploaded documents.
2. If you changed `database/schema.sql` or `database/seed.sql`, also run:
   ```bash
   C:\xampp\php\php.exe scripts/build_infinityfree_sql.php
   ```

---

## 2. Create the hosting account and database

1. In the InfinityFree client area, create a hosting account and choose your (sub)domain.
2. Open the **Control Panel** → **MySQL Databases** → create a database (e.g. `sia`).
   InfinityFree adds a prefix, so the real name looks like `if0_12345678_sia`.
3. On the same page (or **Account Details**), note these four values — you need them in §4:
   - **MySQL Hostname** — looks like `sql123.infinityfree.com` (**not** `localhost`)
   - **MySQL Username** — looks like `if0_12345678`
   - **MySQL Password** — your hosting account (vPanel) password
   - **Database name** — `if0_12345678_sia`

---

## 3. Import the database (phpMyAdmin)

The import files are in **`database/infinityfree/`** on your PC. **Do not upload this folder to the website.**

In the Control Panel open **phpMyAdmin** for your database, then use **Import** for each file, **in this order**:

| Order | File | Required? | What it does |
|---|---|---|---|
| 1 | `01_schema.sql` | ✅ Yes | Creates all 51 tables (utf8mb4). Contains **no** `DROP` statements: if tables already exist it stops with *"Table … already exists"* instead of overwriting data. |
| 2 | `02_reference_data.sql` | ✅ Yes | Programs, SHS strands, subjects, curricula, fee templates, scholarships, system settings, public announcements. No people/personal data. |
| 2b | `optional_demo_data.sql` | ❌ Optional | Fictional demo staff/students/applications for a **demo only**. All demo accounts share published passwords (`admin123` / `password123`). Never use on a real site; if you do import it, import it **before** step 3 and change/deactivate the demo accounts immediately. |
| 3 | `03_first_superadmin.sql` | ✅ Yes | Creates **your** administrator account (no default password). Edit it first — see below. |

**Creating your administrator (step 3):**

1. On your PC run this, type the password you want, and press Enter (it prints a hash starting with `$2y$`):
   ```bash
   C:\xampp\php\php.exe -r "echo password_hash(trim(fgets(STDIN)), PASSWORD_DEFAULT), PHP_EOL;"
   ```
2. Open `03_first_superadmin.sql` in Notepad. Replace `CHANGE_ME_FIRST`, `CHANGE_ME_LAST`,
   `admin@CHANGE_ME.example` (twice), and `PASTE_BCRYPT_HASH_HERE` (**twice** — use *Find & Replace*).
3. In phpMyAdmin open the **SQL** tab, paste the edited text, click **Go**. The result must show **1 row** with role `superadmin`.
   0 rows means a placeholder was not replaced — nothing was inserted, fix it and run again.
4. Close Notepad **without saving** (or don't commit the edited copy).

**Already have data on another server?** Export it from that phpMyAdmin (*Export → Custom → Structure and data*,
uncheck "Add DROP TABLE", **exclude the two `_view` objects**) and import that instead of files 1–3.

---

## 4. Fill in the configuration (on your PC)

Edit **`dist\infinityfree\htdocs\config\config.php`** (created from `config/config.example.php`) and replace every `CHANGE_ME`:

| Setting | Put here |
|---|---|
| `APP_ENV` | keep `production` (hides error details from visitors) |
| `APP_DEBUG` | keep `false` (see §9 for temporary debugging) |
| `APP_URL` | your site address, no trailing slash, e.g. `https://ttu-enroll.infinityfreeapp.com` (use `http://` until SSL is active, §6) |
| `APP_BASE_PATH` | `''` when the files go directly into `htdocs` (recommended). `'/folder'` only if you upload into `htdocs/folder` |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | the four values from §2 |
| `SMTP_USERNAME`, `SMTP_PASSWORD`, `MAIL_FROM_ADDRESS` | your Gmail address and a **Google App Password** (Google Account → Security → 2-Step Verification → App passwords). Keep port `587` / `tls`. |
| `PAYMONGO_*` | optional; your PayMongo keys (start with test keys `sk_test_…`) |

`config/config.php` is a PHP file, so even if someone requests it in a browser its contents are never shown,
and `.htaccess` blocks the `config/` folder anyway. It is listed in `.gitignore` — **never commit it**.

---

## 5. Upload the files

Use **FileZilla** (recommended; FTP details are in the Control Panel → *FTP Details*) or the online File Manager.

1. Connect, open the **`htdocs`** folder of your domain. Delete InfinityFree's default `index2.html` if present.
2. Upload the **contents** of `dist\infinityfree\htdocs\` into `htdocs\`, so that the server has:
   ```
   htdocs/
   ├── .htaccess        ← must be uploaded (FileZilla: Server → "Force showing hidden files")
   ├── app/   config/   css/   images/   js/   public/   vendor/
   ├── storage/         (logs + LMS files; blocked from the web)
   └── uploads/         (documents/, payments/, scholarships/)
   ```
3. Do **not** upload: `.env`, `database/`, `docs/`, `scripts/`, `*.md`, `schema_dump.sql`, `setup_database.php`,
   `storage/backups/`, or your local `uploads/` contents (they contain applicants' private documents).
   A single `.zip` of the package would exceed InfinityFree's 10 MB per-file limit, so upload the folder itself.

**Writable folders** (the app creates sub-folders itself; default permissions 755 are fine):
`uploads/documents`, `uploads/payments`, `uploads/scholarships`, `storage/logs`,
`storage/uploads/lms/materials`, `storage/uploads/lms/submissions`.

---

## 6. Domain and HTTPS

1. Control Panel → **SSL/TLS** (or *Free SSL Certificates*) → request a certificate for your domain and follow the
   DNS (CNAME) verification steps. Wait until it shows as **active/installed**.
2. Open `https://your-domain/` and make sure it loads.
3. Then force HTTPS: edit `htdocs/.htaccess` and **remove the `#`** from these three lines (near the top of the rewrite block):
   ```apache
   RewriteCond %{HTTPS} off
   RewriteCond %{HTTP:X-Forwarded-Proto} !https
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```
   Both conditions are needed on InfinityFree to avoid an infinite redirect loop. Do **not** also add a redirect in the
   Control Panel's *Redirects* tool for the same domain.
4. Change `APP_URL` in `config/config.php` to `https://…` and re-upload that one file.

---

## 7. PayMongo (only if you use online payments)

- Success/cancel return URLs are built from `APP_URL` automatically (`/applicant/payment_callback.php`).
- Webhooks **cannot** reach InfinityFree free hosting. You may still register `https://your-domain/api/webhooks/paymongo`
  for when you move to other hosting, but do not rely on it here.
- Start with **test** keys; complete a test checkout and confirm the payment shows as verified after you are redirected back.

---

## 8. Post-deployment test checklist

Use a private/incognito window. Tick each item:

- [ ] `https://your-domain/` loads with styles, logo and campus images (no broken images).
- [ ] `https://your-domain/.env`, `/config/config.php`, `/database/schema.sql`, `/uploads/documents/` → **403/404**, never file contents.
- [ ] `https://your-domain/setup_database.php` → **404**.
- [ ] Log in at `/auth/login.php` with your superadmin → admin dashboard opens; sidebar links work.
- [ ] Logout returns to the login page; `/admin/dashboard.php` then redirects to login.
- [ ] Register a new applicant with a real email you own → the 6-digit code **arrives** → verify → log in.
- [ ] *Forgot password* sends a reset email whose link points to your domain (not `localhost`).
- [ ] As the applicant: fill the enrollment form (section list loads = AJAX works), upload a small PDF/JPG document.
- [ ] As admin: open that applicant's documents in Admissions → the file displays.
- [ ] Upload a payment proof image; as cashier, the proof image displays.
- [ ] LMS: faculty and student logins at `/auth/lms_faculty_login.php` and `/auth/lms_student_login.php`, calendar and course pages open.
- [ ] LMS quiz generator: as faculty, open a course's *Quizzes → Generate from content*. Uploaded .pdf, .docx and .pptx materials show a green badge ("PDF text layer", "Word document", "PowerPoint slides") and can be ticked. A grey "PHP zip extension is not enabled" badge means the host has `zip` off; "needs the smalot/pdfparser package" means `vendor/smalot/` or `vendor/composer/` was not fully uploaded.
- [ ] (If PayMongo) test checkout → redirected back → payment verified.
- [ ] After enabling HTTPS: `http://your-domain/` redirects to `https://`, and you stay logged in while navigating.

---

## 9. Troubleshooting

| Symptom | Likely cause → fix |
|---|---|
| **HTTP 500** on every page | `.htaccess` syntax not accepted, or a PHP fatal error. Check the newest lines of `htdocs/storage/logs/php-error.log` (download via FTP). To see the error on screen temporarily, set `'APP_DEBUG' => true` in `config/config.php`, reload, then **set it back to `false`**. |
| "500 Internal Server Error – Something went wrong" only on some pages | Usually the database: wrong `DB_*` values. The log shows `Database connection failed: …`. Re-check the MySQL **hostname** (`sqlXXX.infinityfree.com`, not `localhost`), the `if0_` prefixed database/user names, and the vPanel password. |
| Unstyled page / missing images | `APP_BASE_PATH` wrong (must be `''` for files directly in `htdocs`), or the `css/`, `images/`, `public/` folders weren't fully uploaded. Check FileZilla's *Failed transfers* tab. |
| Every link gives 404 / only the home page works | `.htaccess` missing in `htdocs` (hidden file not uploaded) — enable "Force showing hidden files" in FileZilla and upload it. |
| Redirect loop (`ERR_TOO_MANY_REDIRECTS`) | HTTPS rule enabled without both conditions, or a Control-Panel redirect for the same domain. Re-comment the 3 lines in §6, remove panel redirects, retry after SSL is active. |
| Logged out right after logging in | Cookies blocked, or the site was opened via two different addresses (with/without `www`). Always use the `APP_URL` address. Users whose IP address changes mid-session are logged out by design (session-hijack protection). |
| Emails never arrive | Wrong Gmail App Password, or `SMTP_PORT` not `587`. Errors are written to `storage/logs/php-error.log`. Check the recipient's spam folder. |
| "Table … already exists" during import | The database isn't empty. Import into a new, empty database, or drop the tables yourself only if you are sure nothing in them is needed. |
| "CREATE VIEW command denied" during import | You imported the original `database/schema.sql`. Use `database/infinityfree/01_schema.sql` instead. |
| Quiz generator says PDF reading needs smalot/pdfparser | Re-upload the whole `vendor/` folder (including `vendor/composer/autoload_*.php`, `vendor/smalot/` and `vendor/symfony/`). A partial upload leaves the old autoloader in place. |
| Quiz generator says the PHP zip extension is not enabled | The host's PHP lacks `ZipArchive` and it can't be changed on InfinityFree. .docx/.pptx can't be read there; upload a PDF or put the text in the module description instead. |
| Uploads fail for big files | 10 MB hard limit on InfinityFree. Ask users to compress/scan at lower resolution. |

---

## 10. Backup and rollback

**Before every update:**
1. **Database:** phpMyAdmin → *Export* → *Quick* → SQL → save the file on your PC (with the date in its name).
   (The in-app *Backup & Restore* page also exports SQL, but it builds the whole file in memory and may time out
   on the free plan as data grows — prefer phpMyAdmin.)
2. **Uploaded files:** in FileZilla download `htdocs/uploads/` and `htdocs/storage/uploads/` to your PC.
3. **Code:** keep the previous `dist\infinityfree\htdocs\` folder (rename it, e.g. `htdocs_2026-10-02`) — or note the Git commit you deployed.

**To update:** rebuild the package (§1), keep your filled-in `config/config.php`, upload and overwrite `app/`,
`css/`, `js/`, `images/`, `public/`, `vendor/`, `config/bootstrap.php`, `config/database.php` and `.htaccess`.
Never overwrite or delete the server's `uploads/` and `storage/` folders.

**To roll back:** re-upload the previous code folder over `htdocs`. If the database was changed, in phpMyAdmin
drop the affected tables (or create a fresh empty database) and import your pre-update export.
Do this only after downloading a copy of the current state, so nothing is lost permanently.

---

## 11. What was verified before handoff (local, not on InfinityFree)

- Built package served at a **domain root** (production mode) and the original `/sia` sub-folder layout — 74/74 and 73/73
  HTTP checks: public pages, all referenced assets, logins/logout, role restrictions, CSRF rejection, AJAX endpoints,
  sensitive-file blocking, setup tool disabled, no stack traces in production, session-cookie flags.
- SQL files imported by a MySQL user **without** `CREATE VIEW`/`TRIGGER`/routine privileges (as on free hosting).
- Document upload: valid file accepted, disguised PHP rejected, direct URL blocked, owner/admin can view, other students cannot.
- **Not verifiable locally (check live, §8):** InfinityFree's exact PHP/MySQL versions and extensions, real Gmail
  delivery, PayMongo checkout from the live domain, SSL issuance, the security-check behaviour, and hit limits.

## Configuration reference

| Where | Used for |
|---|---|
| `config/config.php` | Production settings (this guide). Overrides `.env`. Not in Git. |
| `.env` | Local XAMPP development only. Never uploaded. |
| `config/bootstrap.php` | Loads the settings above, sets `BASE_PATH`, error display/logging. No secrets inside. |
