<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table         = 'orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'shipping_fee',
        'discount_amount',
        'total',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByUser(int $userId, int $perPage = 15, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;
        $total  = $this->where('user_id', $userId)->countAllResults(false);
        $items  = $this->where('user_id', $userId)
                       ->orderBy('created_at', 'DESC')
                       ->findAll($perPage, $offset);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function getAdminList(int $perPage = 15, int $page = 1, string $status = ''): array
    {
        $offset = ($page - 1) * $perPage;

        $this->select('orders.*, users.name as customer_name, users.email as customer_email')
             ->join('users', 'users.id = orders.user_id', 'left');

        if ($status !== '') {
            $this->where('orders.status', $status);
        }

        $this->orderBy('orders.created_at', 'DESC');

        $total = $this->countAllResults(false);
        $items = $this->findAll($perPage, $offset);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function generateOrderNumber(): string
    {
        return 'GGD-' . strtoupper(substr(uniqid(), -8)) . '-' . date('Ymd');
    }
}
