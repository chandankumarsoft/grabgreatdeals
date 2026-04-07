<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponUsageModel extends Model
{
    protected $table         = 'coupon_usages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_amount',
        'used_at',
    ];

    public function countByUserAndCoupon(int $userId, int $couponId): int
    {
        return (int) $this->where('user_id', $userId)
                          ->where('coupon_id', $couponId)
                          ->countAllResults();
    }

    public function record(int $couponId, int $userId, int $orderId, float $discountAmount): void
    {
        $this->insert([
            'coupon_id'       => $couponId,
            'user_id'         => $userId,
            'order_id'        => $orderId,
            'discount_amount' => $discountAmount,
            'used_at'         => date('Y-m-d H:i:s'),
        ]);
    }
}
