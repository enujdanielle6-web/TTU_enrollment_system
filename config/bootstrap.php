<?php
/**
 * Central environment bootstrap (idempotent).
 *
 * Configuration sources, highest priority first:
 *   1. Real server environment variables (if the host provides any)
 *   2. config/config.php  - PHP array, used on InfinityFree / shared hosting
 *   3. .env               - legacy local-development file (XAMPP)
 *
 * Also defines:
 *   APP_ROOT   absolute filesystem path of the project root
 *   BASE_PATH  URL prefix of the app ('' at a domain root, '/sia' under http://localhost/sia)
 * and the helpers app_url(), app_absolute_url(), app_is_https(), app_debug().
 */

declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);
define('APP_ROOT', dirname(__DIR__));

date_default_timezone_set('Asia/Manila');

function app_env_is_set(string $name): bool
{
    return getenv($name) !== false || array_key_exists($name, $_ENV);
}

/**
 * Reads a setting loaded by this file (or a real server environment variable).
 * Use this instead of getenv(): shared hosts such as InfinityFree disable putenv(),
 * so values from config/config.php and .env only live in $_ENV there.
 */
function app_env(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }
    if (array_key_exists($name, $_ENV)) {
        return (string) $_ENV[$name];
    }
    return $default;
}

function app_set_env(string $name, string $value): void
{
    if ($name === '' || app_env_is_set($name)) {
        return;
    }
    if (function_exists('putenv')) {
        putenv(sprintf('%s=%s', $name, $value));
    }
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

// 2. config/config.php (returns an associative array)
$__appConfigFile = __DIR__ . '/config.php';
if (is_file($__appConfigFile)) {
    $__appConfig = require $__appConfigFile;
    if (is_array($__appConfig)) {
        foreach ($__appConfig as $__key => $__value) {
            if ($__value === null || is_array($__value)) {
                continue;
            }
            if (is_bool($__value)) {
                $__value = $__value ? 'true' : 'false';
            }
            app_set_env((string) $__key, (string) $__value);
        }
    }
}

// 3. .env (legacy local development)
$__appEnvFile = APP_ROOT . '/.env';
if (is_file($__appEnvFile)) {
    foreach (file($__appEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
        $__line = trim($__line);
        if ($__line === '' || $__line[0] === '#' || $__line[0] === ';' || strpos($__line, '=') === false) {
            continue;
        }
        [$__name, $__value] = explode('=', $__line, 2);
        app_set_env(trim($__name), trim(trim($__value), '"\''));
    }
}
unset($__appConfigFile, $__appConfig, $__key, $__value, $__appEnvFile, $__line, $__name);

/**
 * Whether detailed errors may be shown in the browser.
 * APP_DEBUG wins when set; otherwise debug is on for any APP_ENV other than "production".
 * APP_ENV defaults to "production" so a missing config never leaks stack traces.
 */
function app_debug(): bool
{
    $debug = app_env('APP_DEBUG');
    if ($debug !== null && $debug !== '') {
        return in_array(strtolower($debug), ['1', 'true', 'on', 'yes'], true);
    }
    return (app_env('APP_ENV') ?: 'production') !== 'production';
}

if (app_debug()) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    $__logDir = APP_ROOT . '/storage/logs';
    if (!is_dir($__logDir)) {
        @mkdir($__logDir, 0755, true);
    }
    if (is_dir($__logDir) && is_writable($__logDir)) {
        ini_set('error_log', $__logDir . '/php-error.log');
    }
    unset($__logDir);
}

/**
 * Whether login pages show the one-click "Fast Demo Access" buttons (they publish demo passwords).
 * DEMO_LOGINS wins when set; otherwise they only show in debug/local mode.
 */
function app_show_demo_logins(): bool
{
    $flag = app_env('DEMO_LOGINS');
    if ($flag !== null && $flag !== '') {
        return in_array(strtolower($flag), ['1', 'true', 'on', 'yes'], true);
    }
    return app_debug();
}

function app_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
        return true;
    }
    if (strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https') {
        return true;
    }
    return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function app_normalize_base_path(string $path): string
{
    $path = str_replace('\\', '/', trim($path));
    // Restrict to safe URL path characters; BASE_PATH is embedded in HTML, JS and SQL literals.
    $path = preg_replace('#[^A-Za-z0-9._~/-]#', '', $path) ?? '';
    $path = rtrim($path, '/');
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    return $path;
}

function app_detect_base_path(): string
{
    if (app_env_is_set('APP_BASE_PATH')) {
        return app_normalize_base_path((string) app_env('APP_BASE_PATH'));
    }

    if (PHP_SAPI !== 'cli' && !empty($_SERVER['SCRIPT_NAME'])) {
        // e.g. /sia/public/index.php -> /sia ; /public/index.php -> '' ; /sia/setup_database.php -> /sia
        $dir = str_replace('\\', '/', dirname((string) $_SERVER['SCRIPT_NAME']));
        if (str_ends_with($dir, '/public')) {
            $dir = substr($dir, 0, -7);
        }
        return app_normalize_base_path($dir === '/' || $dir === '.' ? '' : $dir);
    }

    $appUrl = (string) (app_env('APP_URL') ?: '');
    if ($appUrl !== '') {
        return app_normalize_base_path((string) (parse_url($appUrl, PHP_URL_PATH) ?? ''));
    }

    return '';
}

define('BASE_PATH', app_detect_base_path());

/**
 * Root-relative URL for an application path, e.g. app_url('/auth/login.php') -> '/sia/auth/login.php'.
 */
function app_url(string $path = '/'): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

/**
 * Absolute URL for links that leave the browser (emails, payment gateway return URLs).
 * Uses APP_URL when configured so the Host header cannot be used to poison links.
 */
function app_absolute_url(string $path = '/'): string
{
    $appUrl = rtrim(trim((string) (app_env('APP_URL') ?: '')), '/');
    if ($appUrl === '') {
        $scheme = app_is_https() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $appUrl = $scheme . '://' . $host . BASE_PATH;
    }
    return $appUrl . '/' . ltrim($path, '/');
}
