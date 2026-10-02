<?php
declare(strict_types=1);

namespace App\Config;

use RuntimeException;

/**
 * Encapsulates PayMongo gateway credentials and endpoint configurations loaded
 * securely from environment variables. Secrets are never hardcoded or exposed to client-side assets.
 */
class PayMongoConfig
{
    private static function ensureEnvLoaded(): void
    {
        if (getenv('PAYMONGO_SECRET_KEY') !== false && getenv('PAYMONGO_SECRET_KEY') !== '') {
            return;
        }
        $envFile = dirname(__DIR__, 2) . '/.env';
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
    }

    /**
     * Retrieve the secret API key used for server-side PayMongo API authentication.
     *
     * @throws RuntimeException If PAYMONGO_SECRET_KEY is missing or empty
     */
    public static function getSecretKey(): string
    {
        self::ensureEnvLoaded();
        $key = getenv('PAYMONGO_SECRET_KEY') ?: ($_ENV['PAYMONGO_SECRET_KEY'] ?? ($_SERVER['PAYMONGO_SECRET_KEY'] ?? ''));
        $key = trim((string) $key);

        if (empty($key)) {
            throw new RuntimeException('PayMongo secret key is not configured. Please set PAYMONGO_SECRET_KEY in your .env file.');
        }

        return $key;
    }

    /**
     * Retrieve the public API key.
     */
    public static function getPublicKey(): ?string
    {
        $key = getenv('PAYMONGO_PUBLIC_KEY') ?: ($_ENV['PAYMONGO_PUBLIC_KEY'] ?? ($_SERVER['PAYMONGO_PUBLIC_KEY'] ?? ''));
        $key = trim((string) $key);

        return $key !== '' ? $key : null;
    }

    /**
     * Base URL for the PayMongo REST API.
     */
    public static function getBaseUrl(): string
    {
        $url = getenv('PAYMONGO_BASE_URL') ?: ($_ENV['PAYMONGO_BASE_URL'] ?? ($_SERVER['PAYMONGO_BASE_URL'] ?? 'https://api.paymongo.com/v1'));
        return rtrim(trim((string) $url), '/');
    }

    /**
     * Webhook signing secret used to verify PayMongo-Signature headers.
     */
    public static function getWebhookSecret(): ?string
    {
        $secret = getenv('PAYMONGO_WEBHOOK_SECRET') ?: ($_ENV['PAYMONGO_WEBHOOK_SECRET'] ?? ($_SERVER['PAYMONGO_WEBHOOK_SECRET'] ?? ''));
        $secret = trim((string) $secret);

        return $secret !== '' ? $secret : null;
    }

    /**
     * Base URL of the local TTU Enrollment System application.
     */
    public static function getAppUrl(): string
    {
        $appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? ($_SERVER['APP_URL'] ?? ''));
        $appUrl = trim((string) $appUrl);

        if ($appUrl !== '') {
            return rtrim($appUrl, '/');
        }

        // Infer dynamically from request headers if available
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return "{$scheme}://{$host}/sia";
    }

    /**
     * Check if PayMongo has been configured with an active secret key.
     */
    public static function isConfigured(): bool
    {
        try {
            self::getSecretKey();
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }
}
