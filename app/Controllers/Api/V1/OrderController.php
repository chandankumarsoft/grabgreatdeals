<?php

namespace App\Controllers\Api\V1;

use App\Services\OrderService;

class OrderController extends BaseApiController
{
    protected OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    public function checkout()
    {
        $rules = [
            'shipping_name'    => 'required|max_length[100]',
            'shipping_phone'   => 'required|max_length[20]',
            'shipping_address' => 'required|max_length[500]',
            'payment_method'   => 'permit_empty|in_list[cod,card,fpx,ewallet,bank_transfer]',
            'coupon_code'      => 'permit_empty|max_length[50]',
            'notes'            => 'permit_empty|max_length[500]',
        ];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $userId = (int) $this->getAuthUserId();
        $result = $this->orderService->checkout($userId, $this->request->getJSON(true));

        if ($result === 'empty_cart') {
            return $this->respondError('Your cart is empty', [], 422);
        }

        if ($result === 'transaction_failed') {
            return $this->respondError('Order could not be placed. Please try again.', [], 500);
        }

        $couponErrors = [
            'coupon_not_found'              => 'Coupon code not found.',
            'coupon_inactive'               => 'This coupon is not active.',
            'coupon_not_started'            => 'This coupon is not valid yet.',
            'coupon_expired'                => 'This coupon has expired.',
            'coupon_usage_limit_reached'    => 'This coupon has reached its usage limit.',
            'coupon_per_user_limit_reached' => 'You have already used this coupon the maximum number of times.',
            'coupon_min_order_not_met'      => 'Your cart total does not meet the minimum order amount for this coupon.',
        ];

        if (is_string($result) && isset($couponErrors[$result])) {
            return $this->respondError($couponErrors[$result], [], 422);
        }

        if (is_array($result) && isset($result[0]) && is_string($result[0])) {
            return $this->respondError('Stock validation failed', $result, 422);
        }

        return $this->respondSuccess('Order placed successfully', $result, 201);
    }

    public function index()
    {
        $userId  = (int) $this->getAuthUserId();
        $perPage = max(1, min(50, (int) ($this->request->getGet('per_page') ?? 15)));
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $result = $this->orderService->getUserOrders($userId, $perPage, $page);

        return $this->respondSuccess('Orders retrieved', $result);
    }

    public function show(int $orderId)
    {
        $userId = (int) $this->getAuthUserId();
        $order  = $this->orderService->getOrderById($userId, $orderId);

        if (! $order) {
            return $this->respondNotFound('Order not found');
        }

        return $this->respondSuccess('Order retrieved', $order);
    }
}
