<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\HttpException;
use Closure;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        $method = $request->getMethod();
        
        // Only validate CSRF on state-changing methods
        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $token = $request->input('csrf_token') ?? $request->header('X-CSRF-TOKEN');
            
            if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
                throw new HttpException(403, 'Invalid CSRF security token. Please try refreshing and submitting the form again.');
            }
        }

        return $next($request);
    }
}
