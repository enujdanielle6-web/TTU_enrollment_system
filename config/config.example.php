<?php
/**
 * Production configuration template (InfinityFree / shared hosting).
 *
 * HOW TO USE
 *   1. Copy this file to config/config.php (same folder).
 *   2. Replace every value marked CHANGE_ME with your real values.
 *   3. Upload config/config.php to the server. Never commit it - it is listed in .gitignore.
 *
 * Values here override the legacy .env file. Real server environment variables (if any) override both.
 * Local XAMPP development does not need this file; it keeps using .env.
 */

return [
    // --- Application -------------------------------------------------------
    'APP_ENV'       => 'production',   // 'production' hides error details from visitors
    'APP_DEBUG'     => false,          // set true ONLY temporarily while diagnosing an error
    // Public address of the site, no trailing slash. Used in emails and PayMongo return URLs.
    'APP_URL'       => 'https://CHANGE_ME.example.com',
    // URL folder the app lives in: '' when uploaded directly into htdocs (domain root),
    // '/subfolder' if you uploaded it into htdocs/subfolder.
    'APP_BASE_PATH' => '',
    // One-click "Fast Demo Access" buttons on the login pages. Keep false on a real site; set true
    // only for a demo where you imported optional_demo_data.sql (it shows the demo passwords).
    'DEMO_LOGINS'   => false,

    // --- Database (InfinityFree control panel > MySQL Databases) -----------
    // InfinityFree does NOT use "localhost". Copy the "MySQL Hostname" shown in the panel.
    'DB_HOST'     => 'sqlCHANGE_ME.infinityfree.com',
    'DB_PORT'     => '3306',
    'DB_DATABASE' => 'if0_CHANGE_ME_sia',
    'DB_USERNAME' => 'if0_CHANGE_ME',
    'DB_PASSWORD' => 'CHANGE_ME',      // your hosting account (vPanel) password

    // --- Email (Gmail SMTP with an App Password; InfinityFree allows port 587) ---
    'SMTP_HOST'         => 'smtp.gmail.com',
    'SMTP_PORT'         => '587',
    'SMTP_ENCRYPTION'   => 'tls',
    'SMTP_USERNAME'     => 'CHANGE_ME@gmail.com',
    'SMTP_PASSWORD'     => 'CHANGE_ME_16_char_app_password',
    'MAIL_FROM_ADDRESS' => 'CHANGE_ME@gmail.com',
    'MAIL_FROM_NAME'    => 'Triple T University',

    // --- PayMongo (optional) -------------------------------------------------
    // If the secret key is empty, the "pay online" option shows an error and students must use
    // the upload-proof-of-payment option instead. Use TEST keys (sk_test_...) unless you are live.
    // Note: InfinityFree free hosting blocks incoming webhooks; payments are confirmed when the
    // student is redirected back to the site after checkout (see DEPLOYMENT_INFINITYFREE.md).
    'PAYMONGO_SECRET_KEY'     => '',
    'PAYMONGO_PUBLIC_KEY'     => '',
    'PAYMONGO_BASE_URL'       => 'https://api.paymongo.com/v1',
    'PAYMONGO_WEBHOOK_SECRET' => '',
];
