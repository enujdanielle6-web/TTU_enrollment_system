<?php

namespace App\Middleware;

use App\Core\Request;
use Closure;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next);
}
