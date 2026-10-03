<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset OTP - Triple T University</title>
    <style>
        body { font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; }
        .email-container { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        
        /* Header Hero with School Image and Blue Shade Overlay */
        .header-hero { 
            position: relative; 
            background: #0d47a1 url('cid:ttu_campus') no-repeat center center / cover; 
            text-align: center;
        }
        .header-overlay { 
            background: linear-gradient(180deg, rgba(8, 38, 98, 0.85) 0%, rgba(13, 71, 161, 0.90) 100%); 
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
        .greeting { color: #0f172a; font-size: 20px; margin-top: 0; font-weight: 700; }
        
        .code-box { 
            background: #f8fafc; 
            border: 2px dashed #0d6efd; 
            border-radius: 12px; 
            padding: 24px; 
            text-align: center; 
            margin: 28px 0; 
        }
        .otp-code { 
            font-size: 38px; 
            font-weight: 800; 
            letter-spacing: 10px; 
            color: #0d6efd; 
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; 
            margin: 0; 
            line-height: 1;
        }
        .expiry-text { 
            color: #64748b; 
            font-size: 13px; 
            margin-top: 10px; 
            margin-bottom: 0; 
            font-weight: 500;
        }
        .btn-reset {
            display: inline-block;
            background: #0d6efd;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            margin-top: 18px;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }
        .alert-box { 
            background-color: #fff7ed; 
            border-left: 4px solid #f97316; 
            padding: 14px 18px; 
            border-radius: 6px; 
            font-size: 13px; 
            color: #9a3412; 
            margin-top: 24px; 
        }
        .footer { 
            background-color: #f8fafc; 
            padding: 22px; 
            text-align: center; 
            color: #94a3b8; 
            font-size: 12px; 
            border-top: 1px solid #e2e8f0; 
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- School Picture Background with Blue Shade Overlay -->
        <div class="header-hero">
            <div class="header-overlay">
                <img src="cid:ttu_logo" alt="TTU Logo" class="logo-img">
                <h1 class="univ-title">Triple T University</h1>
                <p class="univ-subtitle">
                    <?php if ($portalType === 'faculty'): ?>
                        Faculty LMS Portal &bull; Password Reset
                    <?php elseif ($portalType === 'student'): ?>
                        Student LMS Portal &bull; Password Reset
                    <?php else: ?>
                        Applicant Portal &bull; Password Reset
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="content">
            <h2 class="greeting">Hello <?= htmlspecialchars($recipientName ?? 'User') ?>,</h2>
            <p style="margin-bottom: 16px;">
                We received a request to reset the password for your 
                <?php if ($portalType === 'faculty'): ?>
                    <strong>Faculty LMS account</strong>
                <?php elseif ($portalType === 'student'): ?>
                    <strong>Student LMS account</strong>
                <?php else: ?>
                    <strong>Applicant account</strong>
                <?php endif; ?>
                at <strong>Triple T University</strong>.
            </p>
            
            <p style="margin-bottom: 8px;">Please use the 6-digit one-time verification code below to set your new password:</p>

            <div class="code-box">
                <div class="otp-code"><?= htmlspecialchars($code ?? '000000') ?></div>
                <p class="expiry-text">
                    <span style="color: #ef4444; font-weight: 600;">&#9201;</span> This code will expire in <strong>15 minutes</strong>.
                </p>
                <?php if (!empty($resetUrl)): ?>
                <div style="margin-top: 16px;">
                    <a href="<?= htmlspecialchars($resetUrl) ?>" class="btn-reset">Reset Password Directly &rarr;</a>
                </div>
                <?php endif; ?>
            </div>

            <p style="font-size: 13.5px; color: #64748b; margin-bottom: 0;">
                Enter this 6-digit code on the password reset page in your browser. If you did not make this request, please safely disregard this email—your account remains secure.
            </p>

            <div class="alert-box">
                <strong>Security Reminder:</strong> TTU staff will never ask for your password or verification code. Never share this code with anyone.
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 5px 0; font-weight: 600; color: #64748b;">
                Triple T University <?= ($portalType === 'faculty' || $portalType === 'student') ? 'Learning Management System' : 'Admissions & Enrollment System' ?>
            </p>
            <p style="margin: 0;">&copy; <?= date('Y') ?> Triple T University. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
