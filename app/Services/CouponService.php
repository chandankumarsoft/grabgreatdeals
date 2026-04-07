<?php

namespace App\Services;

use App\Models\CouponModel;
use App\Models\CouponUsageModel;

class CouponService
{
    protected CouponModel      $couponModel;
    protected CouponUsageModel $usageModel;

    public function __construct()
    {
        $this->couponModel = new CouponModel();
        $this->usageModel  = new CouponUsageModel();
    }

    /**
     * Validate a coupon code against business rules and return either
     * the coupon array or an error string.
     *
     * @param string $code        Coupon code submitted by the customer
     * @param int    $userId      Authenticated user ID
     * @param float  $orderTotal  Current cart / order subtotal BEFORE discount
     *
     * @return array|string  Coupon array on success, error key string on failure
     */
    public function validate(string $code, int $userId, float $orderTotal): array|string
    {
        $coupon = $this->couponModel->findByCode($code);

        if (! $coupon) {
            return 'coupon_not_found';
        }

        if (! $coupon['is_active']) {
            return 'coupon_inactive';
        }

        $now = date('Y-m-d H:i:s');

        if ($coupon['starts_at'] && $coupon['starts_at'] > $now) {
            return 'coupon_not_started';
        }

        if ($coupon['expires_at'] && $coupon['expires_at'] < $now) {
            return 'coupon_expired';
        }

        if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            return 'coupon_usage_limit_reached';
        }

        if ($coupon['usage_limit_per_user'] !== null) {
            $userUses = $this->usageModel->countByUserAndCoupon($userId, (int) $coupon['id']);

            if ($userUses >= $coupon['usage_limit_per_user']) {
                return 'coupon_per_user_limit_reached';
            }
        }

        if ($orderTotal < (float) $coupon['min_order_amount']) {
            return 'coupon_min_order_not_met';
        }

        return $coupon;
    }

    /**
     * Calculate the discount amount for a valid coupon.
     */
    public function calculateDiscount(array $coupon, float $orderTotal): float
    {
        if ($coupon['type'] === 'percent') {
            $discount = $orderTotal * ((float) $coupon['value'] / 100);

            if ($coupon['max_discount_amount'] !== null) {
                $discount = min($discount, (float) $coupon['max_discount_amount']);
            }
        } else {
            // fixed
            $discount = min((float) $coupon['value'], $orderTotal);
        }

        return round($discount, 2);
    }

    /**
     * Record that a coupon was used on a specific order.
     * Call this inside the checkout transaction AFTER the order is created.
     */
    public function recordUsage(int $couponId, int $userId, int $orderId, float $discountAmount): void
    {
        $this->usageModel->record($couponId, $userId, $orderId, $discountAmount);
        $this->couponModel->incrementUsage($couponId);
    }

    // ─── Admin CRUD ────────────────────────────────────────────────────────────

    public function list(int $perPage = 15, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;
        $total  = $this->couponModel->countAllResults(false);
        $items  = $this->couponModel->orderBy('created_at', 'DESC')->findAll($perPage, $offset);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function create(array $data): array
    {
        $data['code'] = strtoupper($data['code']);
        $id = $this->couponModel->insert($data, true);

        return $this->couponModel->find($id);
    }

    public function update(int $id, array $data): array|false
    {
        $coupon = $this->couponModel->find($id);

        if (! $coupon) {
            return false;
        }

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $this->couponModel->update($id, $data);

        return $this->couponModel->find($id);
    }

    public function delete(int $id): bool
    {
        if (! $this->couponModel->find($id)) {
            return false;
        }

        $this->couponModel->delete($id);

        return true;
    }

    public function getById(int $id): ?array
    {
        return $this->couponModel->find($id);
    }
}
