<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Triple T University</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #0d6efd; color: #ffffff; padding: 30px 20px; text-align: center; }
        .header img { width: 80px; height: auto; margin-bottom: 15px; border-radius: 50%; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px; color: #333333; line-height: 1.6; }
        .content h2 { color: #0d6efd; font-size: 20px; margin-top: 0; }
        .credentials-box { background-color: #f8f9fa; border-left: 4px solid #0d6efd; padding: 15px 20px; margin: 25px 0; border-radius: 4px; }
        .credentials-box p { margin: 8px 0; font-size: 15px; }
        .credentials-box strong { color: #212529; }
        .alert { background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; border: 1px solid #ffeeba; margin-top: 25px; font-size: 14px; }
        .btn { display: inline-block; padding: 12px 25px; background-color: #0d6efd; color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: 600; margin-top: 20px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 13px; border-top: 1px solid #e9ecef; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="cid:ttu_logo" alt="TTU Logo">
            <h1>Welcome to Triple T University!</h1>
        </div>
        <div class="content">
            <h2>Congratulations, <?= htmlspecialchars($firstName ?? 'Student') ?>!</h2>
            <p>We are thrilled to inform you that your enrollment process has been finalized. You are now officially enrolled for the upcoming academic year.</p>
            <p>As an enrolled student, you have been assigned an official TTU Email and Student Credentials which will grant you access to both the <strong>Student Portal</strong> and the <strong>Learning Management System (LMS)</strong>.</p>
            
            <div class="credentials-box">
                <p><strong>TTU Email:</strong> <?= htmlspecialchars($ttuEmail ?? '') ?></p>
                <p><strong>Student Number:</strong> <?= htmlspecialchars($studentNumber ?? '') ?></p>
                <p><strong>Temporary Password:</strong> <?= htmlspecialchars($tempPassword ?? '') ?></p>
            </div>

            <div class="alert">
                <strong>Important Security Notice:</strong> For your security, you are required to reset this temporary password immediately upon your first login.
            </div>

            <center>
                <a href="<?= htmlspecialchars($portalLink ?? 'http://localhost/sia/public/index.php') ?>" class="btn">Login to Student Portal</a>
            </center>
        </div>
        <div class="footer">
            <p>If you have any questions or require assistance, please contact the IT Support Helpdesk or the Registrar's Office.</p>
            <p>&copy; <?= date('Y') ?> Triple T University. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
