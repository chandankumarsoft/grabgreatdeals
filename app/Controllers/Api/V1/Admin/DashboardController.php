<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;
use App\Services\DashboardService;

class DashboardController extends BaseApiController
{
    protected DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    // ─── GET /admin/dashboard ──────────────────────────────────────────────────

    public function index(): \CodeIgniter\HTTP\ResponseInterface
    {
        $summary = $this->dashboardService->getSummary();
        return $this->respondSuccess('Dashboard summary retrieved.', $summary);
    }

    // ─── GET /admin/reports/sales ──────────────────────────────────────────────

    public function salesReport(): \CodeIgniter\HTTP\ResponseInterface
    {
        $startDate = $this->request->getGet('start_date') ?? date('Y-m-01');
        $endDate   = $this->request->getGet('end_date')   ?? date('Y-m-d');
        $groupBy   = $this->request->getGet('group_by')   ?? 'day';

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) ||
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            return $this->respondValidationError(['date' => 'start_date and end_date must be Y-m-d format.']);
        }

        if (! in_array($groupBy, ['day', 'week', 'month'])) {
            return $this->respondValidationError(['group_by' => 'group_by must be day, week, or month.']);
        }

        $report = $this->dashboardService->getSalesReport($startDate, $endDate, $groupBy);
        return $this->respondSuccess('Sales report retrieved.', $report);
    }

    // ─── GET /admin/reports/top-products ──────────────────────────────────────

    public function topProducts(): \CodeIgniter\HTTP\ResponseInterface
    {
        $limit     = (int) ($this->request->getGet('limit')      ?? 10);
        $startDate = $this->request->getGet('start_date') ?? '';
        $endDate   = $this->request->getGet('end_date')   ?? '';
        $sortBy    = $this->request->getGet('sort_by')    ?? 'revenue';

        if ($startDate && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            return $this->respondValidationError(['start_date' => 'start_date must be Y-m-d format.']);
        }
        if ($endDate && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            return $this->respondValidationError(['end_date' => 'end_date must be Y-m-d format.']);
        }
        if (! in_array($sortBy, ['revenue', 'quantity'])) {
            return $this->respondValidationError(['sort_by' => 'sort_by must be revenue or quantity.']);
        }

        $data = $this->dashboardService->getTopProducts($limit, $startDate, $endDate, $sortBy);
        return $this->respondSuccess('Top products retrieved.', $data);
    }

    // ─── GET /admin/reports/orders-by-status ──────────────────────────────────

    public function ordersByStatus(): \CodeIgniter\HTTP\ResponseInterface
    {
        $data = $this->dashboardService->getOrdersByStatus();
        return $this->respondSuccess('Orders by status retrieved.', $data);
    }
}
