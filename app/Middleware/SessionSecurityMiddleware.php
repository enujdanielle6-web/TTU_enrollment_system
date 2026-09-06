<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\HttpException;
use Closure;

class SessionSecurityMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }

        // Session Hijacking and Fixation Protection
        if (!empty($_SESSION['logged_in'])) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            if (!isset($_SESSION['user_ip']) || !isset($_SESSION['user_agent'])) {
                $_SESSION['user_ip'] = $currentIp;
                $_SESSION['user_agent'] = $userAgent;
            } elseif ($_SESSION['user_ip'] !== $currentIp || $_SESSION['user_agent'] !== $userAgent) {
                // Potential hijacking detected
                session_unset();
                session_destroy();
                throw new HttpException(401, 'Session Validation Failed. Your session parameters have changed. For security reasons, please log in again.');
            }

            // Periodic Session Regeneration
            if (!isset($_SESSION['created_time'])) {
                $_SESSION['created_time'] = time();
            } elseif (time() - $_SESSION['created_time'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['created_time'] = time();
            }

            // Enforce forced password reset if flagged
            if (!empty($_SESSION['force_password_reset_required'])) {
                $uri = $request->getUri();
                $allowed = ['/sia/applicant/profile.php', '/sia/applicant/profile_process.php', '/sia/auth/logout.php', '/applicant/profile.php', '/applicant/profile_process.php', '/auth/logout.php'];
                if (!in_array($uri, $allowed, true)) {
                    $res = new \App\Core\Response();
                    $res->redirect('/sia/applicant/profile.php');
                    exit;
                }
            }
        }

        // Generate CSRF Token if not exists
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $next($request);
    }
}
