<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponModel extends Model
{
    protected $table         = 'coupons';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'code',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'usage_limit_per_user',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByCode(string $code): ?array
    {
        return $this->where('code', strtoupper($code))->first();
    }

    public function incrementUsage(int $id): void
    {
        $this->set('used_count', 'used_count + 1', false)->where('id', $id)->update();
    }

    public function decrementUsage(int $id): void
    {
        $this->set('used_count', 'GREATEST(used_count - 1, 0)', false)->where('id', $id)->update();
    }
}
