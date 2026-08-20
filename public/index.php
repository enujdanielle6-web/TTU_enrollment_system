<?php

// Front Controller

// Extremely simple PSR-4 autoloader for the App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\HttpException;

// Load .env environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), '"\'');
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

require_once __DIR__ . '/../app/Helpers/functions.php';

try {
    $request = new Request();
    $response = new Response();
    $router = new Router($request, $response);

    // Load routes
    require_once __DIR__ . '/../app/Routes/web.php';

    // Resolve route
    echo $router->resolve();

} catch (HttpException $e) {
    // Handle expected HTTP Exceptions (404, 403, etc.)
    http_response_code($e->getStatusCode());
    echo "<h1>Error {$e->getStatusCode()}</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";

} catch (\Throwable $e) {
    // Handle unexpected Fatal Exceptions
    http_response_code(500);
    echo "<h1>500 Internal Server Error</h1>";
    
    // In a real production app, we would log this and hide the stack trace.
    // For local development during migration, we can show it:
    if (getenv('APP_ENV') !== 'production') {
        echo "<pre>" . htmlspecialchars((string) $e) . "</pre>";
    } else {
        echo "<p>Something went wrong on our end. Please try again later.</p>";
    }
}
