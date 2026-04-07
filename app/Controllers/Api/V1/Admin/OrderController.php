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
        $perPage = max(1, min(50, (int) ($this->request->getGet('per_page') ?? 15)));
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $status  = $this->request->getGet('status') ?? '';

        if ($status !== '' && ! in_array($status, $this->validStatuses)) {
            return $this->respondValidationError(['status' => 'Invalid status value']);
        }

        $result = $this->orderService->getAdminOrders($perPage, $page, $status);

        return $this->respondSuccess('Orders retrieved', $result);
    }

    public function updateStatus(int $orderId)
    {
        $rules = [
            'status' => 'required|in_list[pending,confirmed,processing,shipped,delivered,cancelled,refunded]',
        ];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $status = $this->request->getJSON(true)['status'];
        $result = $this->orderService->updateStatus($orderId, $status);

        if ($result === false) {
            return $this->respondNotFound('Order not found');
        }

        return $this->respondSuccess('Order status updated', $result);
    }
}
