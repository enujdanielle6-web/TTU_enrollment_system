<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$userId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare('
        SELECT a.*, u.first_name, u.last_name, u.email, u.student_number 
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        WHERE a.user_id = :user_id AND a.status IN ("approved", "enrolled")
        LIMIT 1
    ');
    $stmt->execute(['user_id' => $userId]);
    $app = $stmt->fetch();

    if (!$app) {
        showErrorPage('Access Denied', 'You must have an approved application to print an admission slip.', 403);
    }
} catch (PDOException $e) {
    showErrorPage('Database Error', 'A database error occurred while fetching your admission slip detail.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>TTU Admission Slip - <?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body { font-family: 'Arial', sans-serif; color: #333; line-height: 1.6; max-width: 800px; margin: 40px auto; padding: 20px; }
    .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
    .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
    .header p { margin: 5px 0 0; color: #666; }
    .content-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
    .content-table th, .content-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
    .content-table th { background-color: #f9f9f9; width: 35%; }
    .footer { text-align: center; margin-top: 60px; }
    .signature-line { width: 250px; border-top: 1px solid #333; margin: 0 auto; padding-top: 10px; }
    @media print {
      body { margin: 0; padding: 0; max-width: 100%; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

  <div class="no-print" style="text-align: right; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 8px 16px; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer;">Print Document</button>
  </div>

  <div class="header">
    <h1>Triple T University</h1>
    <h2>Official Admission Slip</h2>
    <p>Academic Year <?= htmlspecialchars((string)($app['school_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <table class="content-table">
    <tr>
      <th>Reference Number</th>
      <td><strong><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
    </tr>
    <tr>
      <th>Applicant Name</th>
      <td><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php if (!empty($app['student_number'])): ?>
    <tr>
      <th>Student Number</th>
      <td><strong><?= htmlspecialchars($app['student_number'], ENT_QUOTES, 'UTF-8') ?></strong></td>
    </tr>
    <?php endif; ?>
    <tr>
      <th>Grade/Year Level</th>
      <td><?= htmlspecialchars((string)($app['grade_level'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
      <th>Semester</th>
      <td><?= htmlspecialchars((string)($app['semester'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
      <th>Strand / Program</th>
      <td><?= htmlspecialchars(getStrandLabel((string)($app['strand'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php if (($app['academic_level'] ?? '') === 'College' && ($app['grade_level'] ?? '') === '1st Year'): ?>
    <tr>
      <th>NSTP Choice</th>
      <td><?= htmlspecialchars((string)($app['nstp'] ?? 'Not Selected'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endif; ?>
    <tr>
      <th>Status</th>
      <td><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $app['status'])), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
      <th>Date Generated</th>
      <td><?= date('F j, Y, g:i a') ?></td>
    </tr>
  </table>

  <div class="footer">
    <div class="signature-line">
      <strong>Office of the Registrar</strong><br>
      <small>Authorized Signature</small>
    </div>
    <p style="margin-top: 30px; font-size: 12px; color: #777;">This is a system generated document. Valid for enrollment purposes only.</p>
  </div>

  <script>
    // Automatically trigger print dialog
    window.onload = function() { window.print(); }
  </script>
</body>
</html>
