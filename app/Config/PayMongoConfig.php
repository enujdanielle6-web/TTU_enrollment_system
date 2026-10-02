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
        require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
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
        self::ensureEnvLoaded();
        $key = getenv('PAYMONGO_PUBLIC_KEY') ?: ($_ENV['PAYMONGO_PUBLIC_KEY'] ?? ($_SERVER['PAYMONGO_PUBLIC_KEY'] ?? ''));
        $key = trim((string) $key);

        return $key !== '' ? $key : null;
    }

    /**
     * Base URL for the PayMongo REST API.
     */
    public static function getBaseUrl(): string
    {
        self::ensureEnvLoaded();
        $url = getenv('PAYMONGO_BASE_URL') ?: ($_ENV['PAYMONGO_BASE_URL'] ?? ($_SERVER['PAYMONGO_BASE_URL'] ?? 'https://api.paymongo.com/v1'));
        return rtrim(trim((string) $url), '/');
    }

    /**
     * Webhook signing secret used to verify PayMongo-Signature headers.
     */
    public static function getWebhookSecret(): ?string
    {
        self::ensureEnvLoaded();
        $secret = getenv('PAYMONGO_WEBHOOK_SECRET') ?: ($_ENV['PAYMONGO_WEBHOOK_SECRET'] ?? ($_SERVER['PAYMONGO_WEBHOOK_SECRET'] ?? ''));
        $secret = trim((string) $secret);

        return $secret !== '' ? $secret : null;
    }

    /**
     * Base URL of the local TTU Enrollment System application.
     */
    public static function getAppUrl(): string
    {
        self::ensureEnvLoaded();
        $appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? ($_SERVER['APP_URL'] ?? ''));
        $appUrl = trim((string) $appUrl);

        if ($appUrl !== '') {
            return rtrim($appUrl, '/');
        }

        // Infer dynamically from the current request (scheme, host and base path)
        return rtrim(app_absolute_url('/'), '/');
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
