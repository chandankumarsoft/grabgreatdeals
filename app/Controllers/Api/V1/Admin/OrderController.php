<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;
use App\Services\OrderService;

class OrderController extends BaseApiController
{
    protected OrderService $orderService;

    protected array $validStatuses = [
        'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded',
    ];

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    public function index()
    {
        ['page' => $page, 'per_page' => $perPage] = $this->getPaginationParams();
        $status = $this->request->getGet('status') ?? '';

        if ($status !== '' && ! in_array($status, $this->validStatuses)) {
            return $this->respondValidationErrors(['status' => 'Invalid status value']);
        }

        $result = $this->orderService->getAdminOrders($perPage, $page, $status);

        return $this->respondSuccess('Orders retrieved', $result);
    }

    public function updateStatus(int $orderId)
    {
        if (! $this->validate('admin_order_update_status')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $status = $this->request->getJSON(true)['status'];
        $result = $this->orderService->updateStatus($orderId, $status);

        if ($result === false) {
            return $this->respondNotFound('Order not found');
        }

        return $this->respondSuccess('Order status updated', $result);
    }
}
