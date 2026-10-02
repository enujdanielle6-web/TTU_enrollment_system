<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PayMongoService;
use App\Services\PaymentService;
use Throwable;

/**
 * Controller responsible for receiving and handling server-to-server webhook notifications
 * from external payment gateways (PayMongo).
 *
 * Publicly reachable endpoint that strictly verifies webhook authenticity using cryptographic
 * HMAC SHA-256 signatures, bypassing standard browser session and CSRF token requirements.
 */
class WebhookController
{
    private PayMongoService $payMongoService;
    private PaymentService $paymentService;

    public function __construct(?PayMongoService $payMongoService = null, ?PaymentService $paymentService = null)
    {
        $this->payMongoService = $payMongoService ?? new PayMongoService();
        $this->paymentService = $paymentService ?? new PaymentService();
    }

    /**
     * Handles incoming PayMongo webhook POST events.
     *
     * @param Request $request
     * @param Response $response
     */
    public function handlePayMongo(Request $request, Response $response): void
    {
        // 1. Enforce POST HTTP Method
        if (!$request->isPost()) {
            $response->json([
                'status'  => 'error',
                'message' => 'Method Not Allowed. PayMongo webhooks must use POST.',
            ], 405);
            return;
        }

        // 2. Extract Byte-for-byte Raw Payload
        $rawPayload = $request->getRawBody();
        if (trim($rawPayload) === '') {
            $response->json([
                'status'  => 'error',
                'message' => 'Empty request payload.',
            ], 400);
            return;
        }

        // 3. Decode and Validate JSON Envelope Structure
        $event = json_decode($rawPayload, true);
        if (!is_array($event) || empty($event['data']) || !is_array($event['data'])) {
            $response->json([
                'status'  => 'error',
                'message' => 'Malformed webhook JSON payload.',
            ], 400);
            return;
        }

        // 4. Verify Cryptographic Paymongo-Signature Header
        $signatureHeader = (string) $request->header('Paymongo-Signature');
        if (trim($signatureHeader) === '') {
            $response->json([
                'status'  => 'error',
                'message' => 'Missing Paymongo-Signature security header.',
            ], 401);
            return;
        }

        try {
            $isValid = $this->payMongoService->verifyWebhookSignature($rawPayload, $signatureHeader);
            if (!$isValid) {
                $response->json([
                    'status'  => 'error',
                    'message' => 'Invalid PayMongo webhook cryptographic signature.',
                ], 401);
                return;
            }
        } catch (Throwable $e) {
            $response->json([
                'status'  => 'error',
                'message' => 'Webhook signature verification failed: ' . $e->getMessage(),
            ], 401);
            return;
        }

        // 5. Reconcile Payment within Atomic Domain Transaction
        try {
            $result = $this->paymentService->processPayMongoWebhook($event, $rawPayload);
            $outcomeStatus = $result['status'] ?? 'unknown';

            // Unknown transaction guard
            if ($outcomeStatus === 'unknown_transaction') {
                $response->json([
                    'status'  => 'error',
                    'message' => $result['message'] ?? 'Transaction not found for session/intent.',
                ], 404);
                return;
            }

            // Acknowledge receipt to PayMongo for verified, duplicate, failed, cancelled, or expired events
            $response->json([
                'status'  => 'success',
                'data'    => $result,
                'message' => $result['message'] ?? 'Webhook processed successfully.',
            ], 200);

        } catch (Throwable $e) {
            // Overpayment or domain rule violations
            $response->json([
                'status'  => 'error',
                'message' => 'Webhook reconciliation error: ' . $e->getMessage(),
            ], 422);
        }
    }
}
