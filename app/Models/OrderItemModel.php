<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table         = 'order_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'variant_label',
        'quantity',
        'unit_price',
        'subtotal',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';

    public function getByOrder(int $orderId): array
    {
        return $this->where('order_id', $orderId)->findAll();
    }

    public function getByOrders(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->whereIn('order_id', $ids)->orderBy('order_id', 'ASC')->findAll();
    }
}
