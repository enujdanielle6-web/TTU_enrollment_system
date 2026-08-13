<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php'; // Loads the .env

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_USERNAME');
    $mail->Password   = getenv('SMTP_PASSWORD');

    // Handle TLS vs SSL
    $enc = getenv('SMTP_ENCRYPTION') ?: 'tls';
    if (strtolower($enc) === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    
    $mail->Port       = getenv('SMTP_PORT') ?: 587;

    // Recipients
    $mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@ttu.edu.ph', getenv('MAIL_FROM_NAME') ?: 'Triple T University');
    $mail->addAddress('enujdanielle6@gmail.com', 'Test User');

    // Embed Logo
    $mail->addEmbeddedImage(__DIR__ . '/images/TTU_LOGO.png', 'ttu_logo');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'TEST SAMPLE: Welcome to Triple T University - Enrollment Finalized';
    
    // Generate body from template
    ob_start();
    $firstName = 'Jane';
    $ttuEmail = 'jane.doe@ttu.edu.ph';
    $studentNumber = '2026-99999';
    $tempPassword = '2026-99999';
    $portalLink = 'http://localhost/sia/public/index.php';
    require __DIR__ . '/app/Views/emails/welcome_credentials.php';
    $mail->Body = ob_get_clean();

    $mail->send();
    echo "Test email successfully sent to kzen0614@gmail.com\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
