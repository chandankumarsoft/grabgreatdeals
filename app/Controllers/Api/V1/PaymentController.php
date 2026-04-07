<?php

namespace App\Controllers\Api\V1;

use App\Services\PaymentService;

class PaymentController extends BaseApiController
{
    protected PaymentService $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    /**
     * GET /orders/{orderId}/payment
     * Returns the payment record for the authenticated user's order.
     */
    public function show(int $orderId)
    {
        $userId  = (int) $this->request->jwtPayload->sub;
        $payment = $this->paymentService->getPaymentForUser($orderId, $userId);

        if ($payment === false) {
            return $this->respondNotFound('Order or payment not found');
        }

        return $this->respondSuccess('Payment retrieved', $payment);
    }
}
