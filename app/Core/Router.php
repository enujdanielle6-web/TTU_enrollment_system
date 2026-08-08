<?php

namespace App\Core;

use Closure;
use Exception;

class Router
{
    public Request $request;
    public Response $response;
    protected array $routes = [];
    protected array $groupAttributes = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, $callback): self
    {
        return $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, $callback): self
    {
        return $this->addRoute('POST', $path, $callback);
    }

    public function put(string $path, $callback): self
    {
        return $this->addRoute('PUT', $path, $callback);
    }

    public function delete(string $path, $callback): self
    {
        return $this->addRoute('DELETE', $path, $callback);
    }

    public function group(array $attributes, Closure $callback): void
    {
        $previousGroupAttributes = $this->groupAttributes;

        if (isset($attributes['middleware'])) {
            $this->groupAttributes['middleware'] = array_merge(
                $this->groupAttributes['middleware'] ?? [],
                (array)$attributes['middleware']
            );
        }

        if (isset($attributes['prefix'])) {
            $this->groupAttributes['prefix'] = ($this->groupAttributes['prefix'] ?? '') . $attributes['prefix'];
        }

        call_user_func($callback, $this);

        $this->groupAttributes = $previousGroupAttributes;
    }

    protected function addRoute(string $method, string $path, $callback): self
    {
        $prefix = $this->groupAttributes['prefix'] ?? '';
        $path = rtrim($prefix . $path, '/');
        if ($path === '') {
            $path = '/';
        }

        // Convert route parameters {id} to Regex named capture groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $path);
        $pattern = "#^" . $pattern . "$#";

        $route = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback,
            'middleware' => $this->groupAttributes['middleware'] ?? []
        ];

        $this->routes[] = $route;
        return $this;
    }

    public function middleware($middleware): self
    {
        if (!empty($this->routes)) {
            $lastIndex = count($this->routes) - 1;
            $currentMiddleware = $this->routes[$lastIndex]['middleware'];
            
            if (is_array($middleware)) {
                $currentMiddleware = array_merge($currentMiddleware, $middleware);
            } else {
                $currentMiddleware[] = $middleware;
            }
            
            $this->routes[$lastIndex]['middleware'] = $currentMiddleware;
        }
        return $this;
    }

    public function resolve()
    {
        $path = $this->request->getUri();
        $method = $this->request->getMethod();

        $matchedRoute = null;
        $matches = [];

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $routeMatches)) {
                $matchedRoute = $route;
                // Filter out numeric keys to keep only named parameters
                $matches = array_filter($routeMatches, 'is_string', ARRAY_FILTER_USE_KEY);
                break;
            }
        }

        if (!$matchedRoute) {
            throw new HttpException(404, "Route not found");
        }

        return $this->dispatch($matchedRoute, $matches);
    }

    protected function dispatch(array $route, array $params)
    {
        $callback = $route['callback'];

        if (is_string($callback)) {
            $callback = function() use ($callback, $params) {
                return $this->renderView($callback, $params);
            };
        } elseif (is_array($callback)) {
            $controller = new $callback[0]();
            $callback[0] = $controller;
        }

        // Build the middleware pipeline
        $pipeline = array_reverse($route['middleware']);
        
        $next = function ($request) use ($callback, $params) {
            if (is_array($callback) || is_callable($callback)) {
                // Pass request, response, and extracted params
                $args = array_merge([$request, $this->response], array_values($params));
                return call_user_func_array($callback, $args);
            }
        };

        foreach ($pipeline as $middlewareClass) {
            $next = function ($request) use ($middlewareClass, $next) {
                $args = [];
                if (strpos($middlewareClass, ':') !== false) {
                    list($middlewareClass, $paramStr) = explode(':', $middlewareClass, 2);
                    $args = explode(',', $paramStr);
                }
                
                $middleware = new $middlewareClass($args);
                return $middleware->handle($request, $next);
            };
        }

        return $next($this->request);
    }

    public function renderView(string $view, array $params = [])
    {
        extract($params);
        ob_start();
        $file = __DIR__ . "/../Views/$view.php";
        if (file_exists($file)) {
            include_once $file;
        } else {
            throw new HttpException(500, "View not found: $view");
        }
        return ob_get_clean();
    }
}
