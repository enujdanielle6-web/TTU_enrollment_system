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

// Load configuration (config/config.php, then .env), BASE_PATH and error settings
require_once __DIR__ . '/../config/bootstrap.php';

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
    // Handle unexpected Fatal Exceptions: always log, only show details when debugging
    error_log('Unhandled exception: ' . $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo "<h1>500 Internal Server Error</h1>";

    if (app_debug()) {
        echo "<pre>" . htmlspecialchars((string) $e) . "</pre>";
    } else {
        echo "<p>Something went wrong on our end. Please try again later.</p>";
    }
}
