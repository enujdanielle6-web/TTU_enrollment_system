<?php

namespace App\Core;

class Request
{
    protected array $data = [];

    public function __construct()
    {
        $this->parseJsonBody();
    }

    public function getMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Method spoofing for PUT/DELETE via POST
        if ($method === 'POST') {
            if (isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }

        return $method;
    }

    public function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }

        // Dynamically remove the base path (e.g. /sia or subdirectories)
        $scriptName = $_SERVER['SCRIPT_NAME']; // e.g. /sia/public/index.php or /public/index.php
        $baseDir = dirname($scriptName);       // e.g. /sia/public or /public

        // If the deployment is configured so that document root is public/, dirname might be \ or /
        if ($baseDir === '\\' || $baseDir === '/') {
            $baseDir = '';
        } else {
            // Also we might want to strip the parent directory if public isn't the docroot
            // usually RewriteBase handles this, but let's be robust
            if (str_ends_with($baseDir, '/public')) {
                $baseDir = substr($baseDir, 0, -7);
            }
        }

        if ($baseDir !== '' && str_starts_with($uri, $baseDir)) {
            $uri = substr($uri, strlen($baseDir));
        }

        // Handle direct access to /public/index.php
        if (str_starts_with($uri, '/public/index.php')) {
            $uri = substr($uri, 17);
        }

        // Ensure we always start with a slash
        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        // Remove trailing slash except for root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        return $uri;
    }

    protected function parseJsonBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if (is_array($data)) {
                $this->data = $data;
            }
        }
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST, $this->data);
    }

    public function input(string $key, $default = null)
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    public function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    public function post(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    public function header(string $key, $default = null)
    {
        // Convert header name to $_SERVER key format (e.g., Content-Type -> HTTP_CONTENT_TYPE)
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        
        if (isset($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }

        // Special cases that don't have HTTP_ prefix
        $special = [
            'CONTENT_TYPE' => 'CONTENT_TYPE',
            'CONTENT_LENGTH' => 'CONTENT_LENGTH'
        ];
        
        $upperKey = strtoupper(str_replace('-', '_', $key));
        if (isset($special[$upperKey]) && isset($_SERVER[$special[$upperKey]])) {
            return $_SERVER[$special[$upperKey]];
        }

        return $default;
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest' || $this->input('ajax') == '1';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }
}
