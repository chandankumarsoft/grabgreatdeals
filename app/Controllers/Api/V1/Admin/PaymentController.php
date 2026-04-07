<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;
use App\Services\PaymentService;

class PaymentController extends BaseApiController
{
    protected PaymentService $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    /**
     * GET /admin/orders/{orderId}/payment
     * Returns the payment record for any order.
     */
    public function show(int $orderId)
    {
        $payment = $this->paymentService->getPaymentForAdmin($orderId);

        if ($payment === false) {
            return $this->respondNotFound('Order or payment not found');
        }

        return $this->respondSuccess('Payment retrieved', $payment);
    }

    /**
     * PUT /admin/orders/{orderId}/payment
     * Manually update payment status and/or transaction ID.
     *
     * Body: { "status": "paid|failed|refunded", "transaction_id": "...", "gateway_response": "..." }
     */
    public function update(int $orderId)
    {
        if (! $this->validate('admin_payment_update')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $result = $this->paymentService->adminUpdatePayment($orderId, $this->request->getJSON(true));

        if ($result === 'order_not_found' || $result === 'payment_not_found') {
            return $this->respondNotFound('Order or payment not found');
        }

        if ($result === 'invalid_transition') {
            return $this->respondError('Payment status transition is not allowed', [], 422);
        }

        return $this->respondSuccess('Payment updated', $result);
    }
}
