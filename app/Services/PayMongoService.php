<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\PayMongoConfig;
use Exception;
use RuntimeException;

/**
 * Custom exception representing PayMongo API errors returned by the gateway.
 */
class PayMongoApiException extends RuntimeException
{
    private int $httpCode;
    private array $responseBody;

    public function __construct(string $message, int $httpCode = 0, array $responseBody = [], ?Exception $previous = null)
    {
        parent::__construct($message, $httpCode, $previous);
        $this->httpCode = $httpCode;
        $this->responseBody = $responseBody;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}

/**
 * PayMongo Gateway Service.
 *
 * Responsible for creating and retrieving PayMongo Checkout Sessions via official REST endpoints.
 * All API keys are loaded securely from environment configurations and never exposed to the frontend.
 */
class PayMongoService
{
    private string $secretKey;
    private string $baseUrl;
    /** @var callable|null */
    private $httpClient;

    /**
     * @param string|null $secretKey PayMongo secret API key (defaults to environment configuration)
     * @param string|null $baseUrl API base URL (defaults to https://api.paymongo.com/v1)
     * @param callable|null $httpClient Optional custom HTTP client callable for testing: fn(string $method, string $url, array $headers, ?string $body): array [status, body]
     */
    public function __construct(?string $secretKey = null, ?string $baseUrl = null, ?callable $httpClient = null)
    {
        $this->secretKey = $secretKey ?? PayMongoConfig::getSecretKey();
        $this->baseUrl = $baseUrl ?? PayMongoConfig::getBaseUrl();
        $this->httpClient = $httpClient;
    }

    /**
     * Creates a hosted PayMongo Checkout Session for a student tuition payment.
     *
     * @param array $params [
     *   'amount'               => (float) payment amount in Philippine Pesos (PHP),
     *   'description'          => (string) assessment or payment description,
     *   'customer_name'        => (string) full name of applicant/student,
     *   'customer_email'       => (string) email address for receipt and billing,
     *   'customer_phone'       => (string) contact number,
     *   'metadata'             => (array) custom key-values linked to session (assessment_id, user_id, payment_id),
     *   'success_url'          => (string|null) return URL upon successful payment,
     *   'cancel_url'           => (string|null) return URL upon cancellation,
     *   'payment_method_types' => (array|null) allowed payment channels (card, gcash, paymaya, grab_pay, dob, dob_ubp)
     * ]
     * @return array [
     *   'checkout_session_id' => string (e.g. cs_...),
     *   'checkout_url'        => string,
     *   'payment_intent_id'   => string|null,
     *   'status'              => string,
     *   'raw'                 => array
     * ]
     * @throws PayMongoApiException On PayMongo API validation or authentication error
     * @throws RuntimeException On network or invalid input failure
     */
    public function createCheckoutSession(array $params): array
    {
        $amount = (float) ($params['amount'] ?? 0.0);
        if ($amount < 100.0) {
            throw new RuntimeException('PayMongo minimum payment amount is ₱100.00.');
        }

        // Convert PHP amount to centavos (1 PHP = 100 centavos)
        $amountCentavos = (int) round($amount * 100);

        $description = trim((string) ($params['description'] ?? 'Triple T University Tuition Payment'));
        $customerName = trim((string) ($params['customer_name'] ?? 'TTU Student'));
        $customerEmail = trim((string) ($params['customer_email'] ?? 'student@ttu.edu.ph'));
        $customerPhone = trim((string) ($params['customer_phone'] ?? '09123456789'));
        $metadata = (array) ($params['metadata'] ?? []);

        $appUrl = PayMongoConfig::getAppUrl();
        $successUrl = !empty($params['success_url'])
            ? (string) $params['success_url']
            : "{$appUrl}/applicant/payment_callback.php?session_id={CHECKOUT_SESSION_ID}";
        $cancelUrl = !empty($params['cancel_url'])
            ? (string) $params['cancel_url']
            : "{$appUrl}/applicant/payment_callback.php?session_id={CHECKOUT_SESSION_ID}&cancelled=1";

        $paymentMethodTypes = !empty($params['payment_method_types'])
            ? (array) $params['payment_method_types']
            : ['card', 'gcash', 'paymaya', 'grab_pay', 'dob', 'dob_ubp'];

        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => [
                        'name'  => $customerName !== '' ? $customerName : 'TTU Student',
                        'email' => $customerEmail !== '' ? $customerEmail : 'student@ttu.edu.ph',
                        'phone' => $customerPhone !== '' ? $customerPhone : '09123456789',
                    ],
                    'send_email_receipt'   => true,
                    'show_description'     => true,
                    'show_line_items'      => true,
                    'description'          => $description,
                    'line_items'           => [
                        [
                            'currency'    => 'PHP',
                            'amount'      => $amountCentavos,
                            'name'        => 'Tuition Assessment Payment',
                            'quantity'    => 1,
                            'description' => $description,
                        ],
                    ],
                    'payment_method_types' => $paymentMethodTypes,
                    'success_url'          => $successUrl,
                    'cancel_url'           => $cancelUrl,
                    'metadata'             => (object) $metadata,
                ],
            ],
        ];

        $endpoint = "{$this->baseUrl}/checkout_sessions";
        $response = $this->sendRequest('POST', $endpoint, $payload);

        $data = $response['data'] ?? [];
        $attributes = $data['attributes'] ?? [];

        $checkoutSessionId = $data['id'] ?? null;
        $checkoutUrl = $attributes['checkout_url'] ?? null;

        if (empty($checkoutSessionId) || empty($checkoutUrl)) {
            throw new PayMongoApiException('PayMongo response did not contain a valid checkout session ID or URL.', 200, $response);
        }

        $paymentIntentId = null;
        if (isset($attributes['payment_intent']['id'])) {
            $paymentIntentId = (string) $attributes['payment_intent']['id'];
        } elseif (is_string($attributes['payment_intent'] ?? null)) {
            $paymentIntentId = (string) $attributes['payment_intent'];
        }

        return [
            'checkout_session_id' => (string) $checkoutSessionId,
            'checkout_url'        => (string) $checkoutUrl,
            'payment_intent_id'   => $paymentIntentId,
            'status'              => (string) ($attributes['status'] ?? 'active'),
            'raw'                 => $response,
        ];
    }

    /**
     * Retrieve an existing Checkout Session by its ID.
     *
     * @param string $sessionId PayMongo checkout session ID (cs_...)
     * @return array Decoded response payload
     */
    public function getCheckoutSession(string $sessionId): array
    {
        $sessionId = trim($sessionId);
        if (empty($sessionId)) {
            throw new RuntimeException('Checkout session ID is required.');
        }

        $endpoint = "{$this->baseUrl}/checkout_sessions/{$sessionId}";
        return $this->sendRequest('GET', $endpoint);
    }

    /**
     * Expires an active checkout session to prevent subsequent payments.
     *
     * @param string $sessionId PayMongo checkout session ID
     * @return array Decoded response payload
     */
    public function expireCheckoutSession(string $sessionId): array
    {
        $sessionId = trim($sessionId);
        if (empty($sessionId)) {
            throw new RuntimeException('Checkout session ID is required.');
        }

        $endpoint = "{$this->baseUrl}/checkout_sessions/{$sessionId}/expire";
        return $this->sendRequest('POST', $endpoint, []);
    }

    /**
     * Verifies the cryptographic Paymongo-Signature header against the raw payload.
     *
     * In accordance with official PayMongo documentation:
     * 1. Extracts timestamp `t`, and signatures `te` (test) or `li` (live).
     * 2. Replay attack mitigation: ensures timestamp is within tolerance window.
     * 3. Computes HMAC SHA-256 of `{$timestamp}.{$rawPayload}` using webhook secret.
     * 4. Securely compares computed hash against signature with hash_equals.
     *
     * @param string $rawPayload Byte-for-byte JSON payload received from the gateway
     * @param string $signatureHeader Value of Paymongo-Signature header (e.g. t=...,te=...,li=...)
     * @param string|null $webhookSecret Webhook secret key (defaults to PayMongoConfig::getWebhookSecret())
     * @param int $tolerance Maximum allowable timestamp drift in seconds (default 300, 0 to disable)
     * @return bool True if authentic and valid, false otherwise
     * @throws RuntimeException If webhook secret is not configured
     */
    public function verifyWebhookSignature(
        string $rawPayload,
        string $signatureHeader,
        ?string $webhookSecret = null,
        int $tolerance = 300
    ): bool {
        $secret = $webhookSecret ?? PayMongoConfig::getWebhookSecret();
        if (empty($secret)) {
            throw new RuntimeException('PayMongo webhook secret is not configured.');
        }

        $signatureHeader = trim($signatureHeader);
        if ($signatureHeader === '' || $rawPayload === '') {
            return false;
        }

        $parts = explode(',', $signatureHeader);
        $signatureMap = [];
        foreach ($parts as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $signatureMap[trim($kv[0])] = trim($kv[1]);
            }
        }

        if (empty($signatureMap['t'])) {
            return false;
        }

        $timestamp = (int) $signatureMap['t'];
        if ($timestamp <= 0) {
            return false;
        }

        // Replay attack mitigation: Verify timestamp is within tolerance window
        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $signatureString = "{$timestamp}.{$rawPayload}";
        $computedHash = hash_hmac('sha256', $signatureString, $secret);

        $te = $signatureMap['te'] ?? null;
        $li = $signatureMap['li'] ?? null;

        if ($te === null && $li === null) {
            return false;
        }

        if ($te !== null && hash_equals($computedHash, $te)) {
            return true;
        }

        if ($li !== null && hash_equals($computedHash, $li)) {
            return true;
        }

        return false;
    }

    /**
     * Constructs a valid Paymongo-Signature header string (utility for unit testing and simulators).
     *
     * @param string $rawPayload Raw JSON string
     * @param string $secret Webhook secret key
     * @param int|null $timestamp Unix timestamp (defaults to current time)
     * @param bool $livemode Whether to sign as livemode ('li') or testmode ('te')
     * @return string Formatted header value (e.g. t=1614588795,te=...)
     */
    public static function generateSignatureHeader(
        string $rawPayload,
        string $secret,
        ?int $timestamp = null,
        bool $livemode = false
    ): string {
        $timestamp = $timestamp ?? time();
        $signatureString = "{$timestamp}.{$rawPayload}";
        $hash = hash_hmac('sha256', $signatureString, $secret);

        return $livemode
            ? "t={$timestamp},li={$hash}"
            : "t={$timestamp},te={$hash}";
    }

    /**
     * Sends an authenticated HTTP request to the PayMongo API.
     *
     * @param string $method HTTP Method (GET, POST)
     * @param string $url Target endpoint URL
     * @param array|null $payload Optional JSON request body
     * @return array Decoded JSON response
     * @throws PayMongoApiException On HTTP error response (4xx, 5xx)
     * @throws RuntimeException On network, connection, or decoding failure
     */
    private function sendRequest(string $method, string $url, ?array $payload = null): array
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->secretKey . ':'),
        ];

        $jsonBody = $payload !== null ? json_encode($payload, JSON_THROW_ON_ERROR) : null;

        // Custom HTTP client hook for testing / mocking
        if ($this->httpClient !== null) {
            $clientResult = ($this->httpClient)($method, $url, $headers, $jsonBody);
            $httpCode = (int) ($clientResult['status'] ?? 0);
            $responseRaw = (string) ($clientResult['body'] ?? '');
        } else {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            ]);

            if ($jsonBody !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            }

            $responseRaw = curl_exec($ch);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlErrno !== 0) {
                throw new RuntimeException("PayMongo API connection failed: {$curlError} (cURL error {$curlErrno})");
            }
        }

        $decoded = json_decode((string) $responseRaw, true);
        if (!is_array($decoded)) {
            $decoded = ['raw' => (string) $responseRaw];
        }

        // Check for HTTP errors (4xx, 5xx)
        if ($httpCode >= 400 || $httpCode === 0) {
            $errorMsg = 'PayMongo API request failed with status ' . $httpCode . '.';
            if (!empty($decoded['errors']) && is_array($decoded['errors'])) {
                $details = [];
                foreach ($decoded['errors'] as $err) {
                    $details[] = $err['detail'] ?? ($err['code'] ?? 'Unknown error');
                }
                $errorMsg .= ' ' . implode(' | ', $details);
            } elseif (!empty($decoded['message'])) {
                $errorMsg .= ' ' . $decoded['message'];
            }

            throw new PayMongoApiException($errorMsg, $httpCode, $decoded);
        }

        return $decoded;
    }
}
