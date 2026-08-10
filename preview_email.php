<?php
// Mock Data to preview the email template
$firstName = 'Juan';
$ttuEmail = 'juan.delacruz@ttu.edu.ph';
$studentNumber = '2026-12345';
$tempPassword = '2026-12345';
$portalLink = 'http://localhost/sia/public/index.php';

// Include the view directly
require __DIR__ . '/app/Views/emails/welcome_credentials.php';
