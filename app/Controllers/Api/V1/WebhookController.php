<?php

namespace App\Controllers\Api\V1;

use App\Services\PaymentService;

class WebhookController extends BaseApiController
{
    protected PaymentService $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    /**
     * POST /webhooks/payment/{gateway}
     *
     * Receives a payment status notification from an external gateway.
     * The gateway identifier (e.g. 'fpx', 'card', 'ewallet') is passed as a
     * route segment so each provider can be differentiated in logs.
     *
     * Expected body (JSON):
     * {
     *   "order_number":   "GGD-XXXXXXXX-YYYYMMDD",
     *   "status":         "success|failed|refunded",
     *   "transaction_id": "TXN123"          // optional
     * }
     *
     * Returns HTTP 200 on success so the gateway stops retrying.
     * Returns HTTP 400/404 on known errors so failures are visible.
     *
     * NOTE: Before going live, add HMAC/signature verification inside
     *       PaymentService::handleWebhook() for each gateway provider.
     */
    public function payment(string $gateway)
    {
        $allowedGateways = ['fpx', 'card', 'ewallet', 'bank_transfer', 'cod'];

        if (! in_array($gateway, $allowedGateways, true)) {
            return $this->respondNotFound('Unknown payment gateway');
        }

        $body = $this->request->getJSON(true);

        if (empty($body)) {
            return $this->respondError('Empty payload', [], 400);
        }

        $result = $this->paymentService->handleWebhook($gateway, $body);

        if ($result === 'invalid_payload') {
            return $this->respondError('Missing required fields: order_number, status', [], 400);
        }

        if ($result === 'order_not_found' || $result === 'payment_not_found') {
            return $this->respondNotFound('Order not found');
        }

        if ($result === 'unknown_status') {
            return $this->respondError('Unrecognised status value', [], 422);
        }

        return $this->respondSuccess('Webhook processed', $result);
    }
}
