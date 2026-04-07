<?php

namespace App\Services;

use App\Models\OrderModel;
use App\Models\PaymentModel;

class PaymentService
{
    protected OrderModel   $orderModel;
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        $this->orderModel   = new OrderModel();
        $this->paymentModel = new PaymentModel();
    }

    /**
     * Get payment for an order, scoped to the owning customer.
     */
    public function getPaymentForUser(int $orderId, int $userId): array|false
    {
        $order = $this->orderModel
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();

        if (! $order) {
            return false;
        }

        $payment = $this->paymentModel->getByOrder($orderId);

        return $payment ?? false;
    }

    /**
     * Get payment for an order (admin, no user scope).
     */
    public function getPaymentForAdmin(int $orderId): array|false
    {
        $order = $this->orderModel->find($orderId);

        if (! $order) {
            return false;
        }

        $payment = $this->paymentModel->getByOrder($orderId);

        return $payment ?? false;
    }

    /**
     * Admin: manually update payment status and optional transaction ID.
     *
     * Allowed transitions:
     *   pending  → paid | failed | refunded
     *   paid     → refunded
     *
     * Returns the updated payment record or an error string.
     */
    public function adminUpdatePayment(int $orderId, array $data): array|string
    {
        $order = $this->orderModel->find($orderId);
        if (! $order) {
            return 'order_not_found';
        }

        $payment = $this->paymentModel->getByOrder($orderId);
        if (! $payment) {
            return 'payment_not_found';
        }

        $allowed = [
            'pending' => ['paid', 'failed', 'refunded'],
            'paid'    => ['refunded'],
            'failed'  => ['paid'],
        ];

        $newStatus = $data['status'] ?? $payment['status'];

        if (
            isset($allowed[$payment['status']]) &&
            ! in_array($newStatus, $allowed[$payment['status']], true)
        ) {
            return 'invalid_transition';
        }

        $update = ['status' => $newStatus];

        if (! empty($data['transaction_id'])) {
            $update['transaction_id'] = $data['transaction_id'];
        }

        if (! empty($data['gateway_response'])) {
            $update['gateway_response'] = $data['gateway_response'];
        }

        if ($newStatus === 'paid' && empty($payment['paid_at'])) {
            $update['paid_at'] = date('Y-m-d H:i:s');
        }

        $this->paymentModel->update($payment['id'], $update);

        return $this->paymentModel->find($payment['id']);
    }

    /**
     * Webhook: verify and process an incoming payment notification.
     *
     * Currently a scaffold — returns the parsed payload for logging.
     * Extend this method when integrating a real payment gateway.
     *
     * @param string $gateway  e.g. 'fpx', 'card', 'ewallet'
     * @param array  $payload  Raw POST body decoded to array
     */
    public function handleWebhook(string $gateway, array $payload): array|string
    {
        // --- Signature / HMAC verification would go here ---
        // e.g. $this->verifyHmac($gateway, $payload);

        $orderNumber = $payload['order_number'] ?? null;
        $status      = $payload['status']       ?? null;
        $txnId       = $payload['transaction_id'] ?? null;

        if (! $orderNumber || ! $status) {
            return 'invalid_payload';
        }

        $order = $this->orderModel->where('order_number', $orderNumber)->first();
        if (! $order) {
            return 'order_not_found';
        }

        $payment = $this->paymentModel->getByOrder((int) $order['id']);
        if (! $payment) {
            return 'payment_not_found';
        }

        // Map gateway status → internal status
        $statusMap = [
            'success'  => 'paid',
            'paid'     => 'paid',
            'failed'   => 'failed',
            'failure'  => 'failed',
            'expired'  => 'failed',
            'refunded' => 'refunded',
        ];

        $internalStatus = $statusMap[strtolower($status)] ?? null;
        if (! $internalStatus) {
            return 'unknown_status';
        }

        $update = [
            'status'           => $internalStatus,
            'gateway_response' => json_encode($payload),
        ];

        if ($txnId) {
            $update['transaction_id'] = $txnId;
        }

        if ($internalStatus === 'paid' && empty($payment['paid_at'])) {
            $update['paid_at'] = date('Y-m-d H:i:s');
        }

        $this->paymentModel->update($payment['id'], $update);

        return [
            'gateway'      => $gateway,
            'order_number' => $orderNumber,
            'status'       => $internalStatus,
        ];
    }
}
