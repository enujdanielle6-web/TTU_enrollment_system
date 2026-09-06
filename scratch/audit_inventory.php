<?php

require_once __DIR__ . '/../vendor/autoload.php';

// 1. Parse app/Routes/web.php to get all routes
$webContent = file_get_contents(__DIR__ . '/../app/Routes/web.php');
preg_match_all('/\$router->(get|post|put|delete|any)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[\s*[\'"]?([^\'",]+)[\'"]?\s*,\s*[\'"]?([^\'",]+)[\'"]?\s*\]/i', $webContent, $matches, PREG_SET_ORDER);

$routes = [];
foreach ($matches as $m) {
    $routes[] = [
        'method' => strtoupper($m[1]),
        'uri' => $m[2],
        'controller' => $m[3],
        'action' => $m[4]
    ];
}

echo "=== REGISTERED ROUTES IN WEB.PHP ===\n";
echo "Total routes found: " . count($routes) . "\n\n";

// 2. Scan all controllers
$controllerFiles = glob(__DIR__ . '/../app/Controllers/*.php');
$subControllerFiles = glob(__DIR__ . '/../app/Controllers/*/*.php');
$subSubControllerFiles = glob(__DIR__ . '/../app/Controllers/*/*/*.php');
$allControllers = array_merge($controllerFiles, $subControllerFiles, $subSubControllerFiles);

$controllerMethods = [];
foreach ($allControllers as $file) {
    $rel = str_replace(realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR, '', realpath($file));
    $content = file_get_contents($file);
    preg_match('/namespace\s+([^;]+);/', $content, $nsMatch);
    preg_match('/class\s+(\w+)/', $content, $classMatch);
    if ($classMatch) {
        $className = ($nsMatch ? $nsMatch[1] . '\\' : '') . $classMatch[1];
        preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $fnMatches);
        $controllerMethods[$className] = [
            'file' => $rel,
            'methods' => $fnMatches[1] ?? []
        ];
    }
}

echo "=== CONTROLLERS FOUND: " . count($controllerMethods) . " ===\n";
foreach ($controllerMethods as $cls => $info) {
    echo "- $cls ({$info['file']}): " . implode(', ', $info['methods']) . "\n";
}

// 3. Scan all views
function getFilesRecursively($dir, $pattern = '*.php') {
    $files = [];
    $items = glob($dir . '/' . $pattern);
    foreach ($items as $item) {
        if (is_file($item)) $files[] = $item;
    }
    foreach (glob($dir . '/*', GLOB_ONLYDIR) as $sub) {
        $files = array_merge($files, getFilesRecursively($sub, $pattern));
    }
    return $files;
}

$viewFiles = getFilesRecursively(__DIR__ . '/../app/Views');
$views = [];
$rootPath = realpath(__DIR__ . '/..');
foreach ($viewFiles as $vf) {
    $rel = str_replace($rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR, '', realpath($vf));
    $views[] = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
}
echo "\n=== VIEWS FOUND: " . count($views) . " ===\n";
sort($views);
foreach ($views as $v) {
    echo "  $v\n";
}

// 4. Scan models, services, core, middleware
$models = glob(__DIR__ . '/../app/Models/*.php');
echo "\n=== MODELS FOUND: " . count($models) . " ===\n";
foreach ($models as $m) echo "  " . basename($m) . "\n";

$services = glob(__DIR__ . '/../app/Services/*.php');
echo "\n=== SERVICES FOUND: " . count($services) . " ===\n";
foreach ($services as $s) echo "  " . basename($s) . "\n";

$middleware = glob(__DIR__ . '/../app/Middleware/*.php');
echo "\n=== MIDDLEWARE FOUND: " . count($middleware) . " ===\n";
foreach ($middleware as $mw) echo "  " . basename($mw) . "\n";

$core = glob(__DIR__ . '/../app/Core/*.php');
echo "\n=== CORE FOUND: " . count($core) . " ===\n";
foreach ($core as $c) echo "  " . basename($c) . "\n";

// 5. Scan docs/obsidian markdown files
$docFiles = getFilesRecursively(__DIR__ . '/../docs/obsidian', '*.md');
echo "\n=== OBSIDIAN DOCS FOUND: " . count($docFiles) . " ===\n";

