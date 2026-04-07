<?php

namespace App\Controllers\Api\V1;

use App\Services\CouponService;
use App\Services\CartService;

class CouponController extends BaseApiController
{
    protected CouponService $couponService;
    protected CartService   $cartService;

    public function __construct()
    {
        $this->couponService = new CouponService();
        $this->cartService   = new CartService();
    }

    /**
     * POST /coupons/apply
     * Validate a coupon code against the customer's current cart total.
     * Returns the coupon details and calculated discount — does NOT modify the
     * cart; the discount is applied at checkout time via the coupon_code field.
     *
     * Body: { "code": "SAVE10" }
     */
    public function apply()
    {
        $rules = ['code' => 'required|max_length[50]'];

        if (! $this->validate($rules)) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $userId = (int) $this->getAuthUserId();
        $code   = $this->request->getJSON()->code ?? '';

        // Get cart total to validate minimum order
        $cart  = $this->cartService->getCart($userId);
        $total = (float) $cart['total'];

        $result = $this->couponService->validate($code, $userId, $total);

        if (is_string($result)) {
            $messages = [
                'coupon_not_found'             => 'Coupon code not found.',
                'coupon_inactive'              => 'This coupon is not active.',
                'coupon_not_started'           => 'This coupon is not valid yet.',
                'coupon_expired'               => 'This coupon has expired.',
                'coupon_usage_limit_reached'   => 'This coupon has reached its usage limit.',
                'coupon_per_user_limit_reached'=> 'You have already used this coupon the maximum number of times.',
                'coupon_min_order_not_met'     => 'Your cart total does not meet the minimum order amount for this coupon.',
            ];

            return $this->respondError($messages[$result] ?? 'Invalid coupon', [], 422);
        }

        $discount = $this->couponService->calculateDiscount($result, $total);

        return $this->respondSuccess('Coupon applied', [
            'coupon'            => [
                'id'          => $result['id'],
                'code'        => $result['code'],
                'description' => $result['description'],
                'type'        => $result['type'],
                'value'       => $result['value'],
            ],
            'original_total'    => $total,
            'discount_amount'   => $discount,
            'discounted_total'  => round($total - $discount, 2),
        ]);
    }
}
