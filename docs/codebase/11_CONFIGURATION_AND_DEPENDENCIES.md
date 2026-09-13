# 11. CONFIGURATION AND DEPENDENCIES

## Runtime Requirements
- **PHP:** 8.1 or higher (relies on named arguments, enums, match expressions, constructor promotion).
- **Web Server:** Apache 2.4+ with `mod_rewrite` enabled.
- **Extensions:** `pdo_mysql`, `fileinfo`, `json`, `session`, `mbstring`.

## Web Server Rules (`.htaccess`)
- `Options -Indexes`: Prevents directory listing.
- Direct file access prevention: `RewriteRule ^(app|config|database)/ - [F,L]`.
- Front Controller rewrite: `RewriteRule ^(.*)$ public/index.php [QSA,L]`.

## Configuration Files
- `config/app.php`: Timezone (`Asia/Manila`), environment (`development`/`production`).
- `config/database.php`: MariaDB PDO credentials (DB host, port, database name, user, password).

## Composer Dependencies (`composer.json`)
- `phpmailer/phpmailer`: Institutional email notifications and credentials delivery.
- `vlucas/phpdotenv`: Environment variables loading.
