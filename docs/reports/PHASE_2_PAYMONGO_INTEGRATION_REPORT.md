# Phase 2: PayMongo Checkout Integration Report

**TTU Enrollment System**  
**Architecture & Implementation Verification**  
**Phase Completed**: Phase 2 (PayMongo Checkout Integration)  
**Status**: COMPLETED & VERIFIED  

---

## 1. Executive Summary

Phase 2 introduces seamless, secure, and officially compliant online payment checkout integration via **PayMongo** for the TTU Enrollment System.

In strict compliance with architectural constraints:
- **Central Ledger Retained**: `payment_records` remains the single, authoritative financial ledger. No secondary ledger was created.
- **Assessment Engine Reused**: The existing `AssessmentService`, `student_assessments`, and `assessment_items` remain the sole source of truth for tuition assessment and subject snapshot calculations.
- **Registrar Finalization Untouched**: Finalization and matriculation in `EnrollmentService::finalizeEnrollment()` remain completely isolated from client returns.
- **Return Callback Security**: Returning to the system from PayMongo checkout **NEVER** marks a payment as verified. Frontend payment statuses are strictly untrusted; transactions remain `pending` until automated webhook confirmation (scheduled for subsequent phases).
- **Secret Key Protection**: All PayMongo secret API keys are strictly configured through environment variables (`.env`, gitignored), accessed solely through server-side domain services, and never exposed to the browser or JavaScript.
- **No Premature Queue**: The asynchronous payment queue was deferred to Phase 3.

---

## 2. New Components & Architecture

### 2.1. `App\Config\PayMongoConfig`
- **Location**: `app/Config/PayMongoConfig.php`
- **Responsibilities**:
  - Encapsulates configuration loading from `getenv()`, `$_ENV`, and `$_SERVER`.
  - Securely loads:
    - `PAYMONGO_SECRET_KEY`: Secret API key for Basic Auth authentication.
    - `PAYMONGO_PUBLIC_KEY`: Public API key.
    - `PAYMONGO_BASE_URL`: Endpoint base URL (defaults to `https://api.paymongo.com/v1`).
    - `PAYMONGO_WEBHOOK_SECRET`: Signing secret for webhook verification.
    - `APP_URL`: System base URL for constructing callbacks.
  - Enforces mandatory secret key validation, throwing a clear `RuntimeException` if missing or unconfigured.

### 2.2. `App\Services\PayMongoService`
- **Location**: `app/Services/PayMongoService.php`
- **Responsibilities**:
  - Communicates directly with the official PayMongo REST API (`/v1/checkout_sessions`).
  - Supports dependency injection of custom HTTP client callables for deterministic automated testing and mocking without network dependency.
  - Implements:
    - `createCheckoutSession(array $params): array`: Converts amounts to centavos, formats billing details, sets payment methods (`card`, `gcash`, `paymaya`, `grab_pay`, `dob`, `dob_ubp`), links TTU metadata (`assessment_id`, `user_id`, `payment_record_id`), and returns the checkout session ID and hosted checkout URL.
    - `getCheckoutSession(string $sessionId): array`: Queries checkout session status from PayMongo.
    - `expireCheckoutSession(string $sessionId): array`: Explicitly expires checkout sessions.
  - Defines `PayMongoApiException` for gateway-level error reporting (preserving HTTP status codes and detailed validation messages).

### 2.3. Domain Orchestration: `PaymentService::initiatePayMongoPayment`
- **Location**: `app/Services/PaymentService.php`
- **Responsibilities**:
  - Enforces domain rules under pessimistic row locks (`SELECT ... FOR UPDATE` on `student_assessments`):
    - Validates minimum amount (₱100.00 for PayMongo checkout).
    - Checks whether assessment is already settled.
    - Enforces balance limits (`amount <= allowableRemaining`).
    - **Duplicate Initiation Protection**: Checks for active in-flight PayMongo sessions created within 30 minutes, preventing simultaneous uncaptured sessions from colliding on the assessment.
  - Creates the initial ledger entry in `payment_records` with status `pending` and `gateway = 'paymongo'`.
  - Invokes `PayMongoService::createCheckoutSession()` with TTU metadata.
  - Links `checkout_session_id`, `checkout_url`, and `payment_intent_id` to the payment record.
  - Logs student activity and commits atomically.
  - On failure, rolls back transaction cleanly with zero orphaned database rows.

### 2.4. Data Access Layer: `PaymentRepository` Additions
- **Location**: `app/Repositories/PaymentRepository.php`
- **New Methods**:
  - `findByCheckoutSessionId(string $sessionId, bool $lock = false): ?array`
  - `findByPaymentIntentId(string $paymentIntentId, bool $lock = false): ?array`
  - `findActivePayMongoSessionForAssessment(int $assessmentId, int $expiryMinutes = 30, bool $lock = false): ?array`
  - `updatePayMongoSession(int $id, string $checkoutSessionId, ?string $checkoutUrl = null, ?string $paymentIntentId = null): bool`
  - Updated `insert()` to support PayMongo gateway fields.

---

## 3. Database Modifications

A dedicated migration script [`scripts/migrations/phase2_paymongo_fields.php`](file:///c:/xampp/htdocs/sia/scripts/migrations/phase2_paymongo_fields.php) was executed on the live MariaDB database and synchronized with [`database/schema.sql`](file:///c:/xampp/htdocs/sia/database/schema.sql) and [`schema_dump.sql`](file:///c:/xampp/htdocs/sia/schema_dump.sql).

### Fields Added to `payment_records`:
| Column | Type | Nullable | Description |
|---|---|---|---|
| `checkout_session_id` | `VARCHAR(150)` | YES | Unique PayMongo Checkout Session identifier (`cs_...`). |
| `payment_intent_id` | `VARCHAR(150)` | YES | Associated PayMongo Payment Intent identifier (`pi_...`). |
| `checkout_url` | `VARCHAR(500)` | YES | Hosted PayMongo checkout URL returned to the student. |
| `gateway` | `VARCHAR(50)` | NO (default: `'manual'`) | Identifies gateway provider (`'manual'`, `'paymongo'`). |
| `gateway_fee` | `DECIMAL(10,2)` | NO (default: `0.00`) | Gateway transaction surcharge or fee. |
| `raw_webhook_payload` | `LONGTEXT` | YES | Reserved for storing raw webhook payloads upon verification. |

### Indexes Added:
- `UNIQUE KEY uq_payment_records_checkout_session (checkout_session_id)`: Prevents duplicate records pointing to the same PayMongo session while allowing multiple `NULL` manual payments.
- `KEY idx_payment_records_gateway (gateway)`: Optimizes gateway-filtered queries.

---

## 4. Server-Side Initiation & Callback Flows

### 4.1. Server-Side Payment Initiation (`ApplicantController::processPayment`)
- **Route**: `POST /applicant/payment_process.php` (`action = 'initiate_paymongo'`)
- **Security Gates**:
  - Session authentication verified (`applicant` / `student` role).
  - CSRF token validation enforced via `CsrfMiddleware`.
  - Clinic health information form requirement enforced.
- **Execution**:
  - Delegates to `PaymentService::initiatePayMongoPayment()`.
  - Redirects user's browser directly to the returned PayMongo checkout URL (or returns JSON for AJAX requests).

### 4.2. Return Callback Flow (`ApplicantController::paymentCallback`)
- **Route**: `GET /applicant/payment_callback.php?session_id={CHECKOUT_SESSION_ID}`
- **Security Enforcement**:
  - Does **NOT** mark payment as verified.
  - Queries `PaymentRepository::findByCheckoutSessionId($sessionId)`.
  - If `status === 'pending'`: Informs student that the session was completed and is awaiting automated provider confirmation.
  - Leaves `student_assessments.total_paid` and `applications.status` completely unchanged.

---

## 5. Verification & Testing

A dedicated test suite [`scripts/tests/test_phase2_paymongo_integration.php`](file:///c:/xampp/htdocs/sia/scripts/tests/test_phase2_paymongo_integration.php) was created and executed against all Phase 2 criteria.

### Test Results Summary:
| # | Test Scenario | Status | Details |
|---|---|---|---|
| 1 | **Valid Checkout Creation** | **PASS** | Session created, checkout URL returned, status set to `pending`. |
| 2 | **Session Association** | **PASS** | `payment_records` correctly associates `checkout_session_id`, `payment_intent_id`, and `checkout_url`. |
| 3 | **Invalid Amount** | **PASS** | Rejects zero, negative, and sub-₱100.00 amounts. |
| 4 | **Already-Paid Assessment** | **PASS** | Rejects initiation on settled assessment. |
| 5 | **Amount Greater Than Balance** | **PASS** | Rejects initiation exceeding allowable remaining balance. |
| 6 | **Missing Configuration** | **PASS** | Throws `RuntimeException` when `PAYMONGO_SECRET_KEY` is missing. |
| 7 | **PayMongo API Failure** | **PASS** | Catches `PayMongoApiException` and rolls back transaction with zero orphaned rows. |
| 8 | **Duplicate Initiation** | **PASS** | Blocks concurrent/duplicate in-flight sessions for the same assessment. |
| 9 | **Return Callback Security** | **PASS** | Client return does **NOT** modify balance, does **NOT** generate receipt, and does **NOT** finalize enrollment. |

**Phase 2 Suite**: **28 PASSED, 0 FAILED** (100% success rate).  
**Phase 1 Regression Suite**: **40 PASSED, 0 FAILED** (100% success rate).  
**Phase 0 Safety Regression Suite**: **11 PASSED, 0 FAILED** (100% success rate).  

---

## 6. Remaining Architectural Limitations (To Be Addressed in Future Phases)

1. **Automated Webhook Handling (Phase 3/4)**:
   - Payments initiated through PayMongo remain `pending` until verified. Phase 4 will introduce `/api/webhooks/paymongo` with cryptographic `PayMongo-Signature` HMAC SHA-256 verification to transition payments to `verified`, issue receipts, and update assessment balances automatically.
2. **Concurrent Payment Queue (Phase 3)**:
   - High-traffic enrollment rushes will be managed by an asynchronous queuing layer to throttle simultaneous checkout sessions and prevent gateway rate limiting.

---

## 7. Conclusion

Phase 2 (PayMongo Checkout Integration) is complete, robustly tested, and fully adheres to the TTU Enrollment System architecture rules.

**Antigravity IDE Agent execution has stopped as instructed.**
