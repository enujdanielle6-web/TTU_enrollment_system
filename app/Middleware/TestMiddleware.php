<?php

namespace App\Middleware;

use App\Core\Request;
use Closure;

class TestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        echo "[Middleware Ran] ";
        header('X-Test-Middleware: Activated');
        return $next($request);
    }
}
