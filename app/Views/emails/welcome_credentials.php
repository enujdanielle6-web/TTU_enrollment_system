<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Triple T University</title>
    <style>
        body { font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        
        .header-hero { 
            position: relative; 
            background: #0d47a1 url('cid:ttu_campus') no-repeat center center / cover; 
            text-align: center;
        }
        .header-overlay { 
            background: linear-gradient(180deg, rgba(8, 38, 98, 0.82) 0%, rgba(13, 71, 161, 0.88) 100%); 
            padding: 42px 24px 36px 24px; 
            text-align: center;
        }
        .logo-img { 
            width: 82px; 
            height: auto; 
            margin-bottom: 12px; 
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.4)); 
            border-radius: 50%;
        }
        .univ-title { 
            margin: 0; 
            font-size: 23px; 
            font-weight: 800; 
            color: #ffffff; 
            letter-spacing: 0.8px; 
            text-shadow: 0 2px 6px rgba(0,0,0,0.5); 
            text-transform: uppercase;
        }
        .univ-subtitle { 
            margin: 6px 0 0 0; 
            font-size: 13.5px; 
            color: #dbeafe; 
            font-weight: 600; 
            letter-spacing: 0.3px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.4); 
        }

        .content { padding: 36px 32px; color: #334155; line-height: 1.65; }
        .content h2 { color: #0d6efd; font-size: 20px; margin-top: 0; font-weight: 700; }
        .credentials-box { background-color: #f8fafc; border-left: 4px solid #0d6efd; padding: 18px 22px; margin: 25px 0; border-radius: 8px; border: 1px solid #e2e8f0; border-left-width: 4px; }
        .credentials-box p { margin: 8px 0; font-size: 14.5px; }
        .credentials-box strong { color: #1e293b; }
        .alert { background-color: #fffbeb; color: #92400e; padding: 15px; border-radius: 8px; border: 1px solid #fde68a; margin-top: 25px; font-size: 13.5px; }
        .btn { display: inline-block; padding: 13px 30px; background-color: #0d6efd; color: #ffffff; text-decoration: none; border-radius: 50px; font-weight: 700; margin-top: 22px; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25); }
        .footer { background-color: #f8fafc; padding: 22px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- School Picture Background with Blue Shade Overlay -->
        <div class="header-hero">
            <div class="header-overlay">
                <img src="cid:ttu_logo" alt="TTU Logo" class="logo-img">
                <h1 class="univ-title">Triple T University</h1>
                <p class="univ-subtitle">Official Student & LMS Credentials</p>
            </div>
        </div>

        <div class="content">
            <h2>Congratulations, <?= htmlspecialchars($firstName ?? 'Student') ?>!</h2>
            <p>We are thrilled to inform you that your enrollment process has been finalized. You are now officially enrolled for the upcoming academic year.</p>
            <p>As an enrolled student, you have been assigned an official TTU Email and Student Credentials which grant you access to both the <strong>Student Portal</strong> and the <strong>Learning Management System (LMS)</strong>.</p>
            
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
            <p style="margin: 0 0 5px 0; font-weight: 600; color: #64748b;">Triple T University Admissions & Registrar's Office</p>
            <p style="margin: 0;">&copy; <?= date('Y') ?> Triple T University. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
