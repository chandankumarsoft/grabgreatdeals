<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'order_id',
        'method',
        'status',
        'amount',
        'transaction_id',
        'gateway_response',
        'paid_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByOrder(int $orderId): ?array
    {
        return $this->where('order_id', $orderId)->first();
    }
}
