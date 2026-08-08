<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        if (empty($_SESSION['logged_in'])) {
            $response = new Response();
            $response->redirect('/sia/auth/login.php');
            exit;
        }

        return $next($request);
    }
}
