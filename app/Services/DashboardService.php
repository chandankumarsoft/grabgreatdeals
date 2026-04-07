<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class DashboardService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    // ─── Summary Stats ─────────────────────────────────────────────────────────

    /**
     * GET /admin/dashboard
     *
     * Returns high-level KPIs:
     *   - total_orders, orders_today, orders_this_month
     *   - total_revenue, revenue_today, revenue_this_month
     *   - total_customers, new_customers_today, new_customers_this_month
     *   - total_products (active), low_stock_count (stock <= 5)
     *   - pending_orders, pending_reviews
     *   - recent_orders  (last 5)
     *   - low_stock_products (stock <= 5, limit 10)
     */
    public function getSummary(): array
    {
        $cacheKey = 'ggd_dashboard_summary_' . date('YmdHi');
        $cached   = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $today      = date('Y-m-d');
        $monthStart = date('Y-m-01');

        // ── Orders ──────────────────────────────────────────────────────────────
        $orders = $this->db->query("
            SELECT
                COUNT(*)                                         AS total_orders,
                SUM(CASE WHEN DATE(created_at) = ?  THEN 1 ELSE 0 END) AS orders_today,
                SUM(CASE WHEN created_at >= ?        THEN 1 ELSE 0 END) AS orders_this_month,
                SUM(CASE WHEN status = 'pending'    THEN 1 ELSE 0 END) AS pending_orders
            FROM orders
        ", [$today, $monthStart])->getRowArray();

        // ── Revenue (from paid/delivered orders) ────────────────────────────────
        $revenue = $this->db->query("
            SELECT
                IFNULL(SUM(total), 0)                                            AS total_revenue,
                IFNULL(SUM(CASE WHEN DATE(created_at) = ? THEN total ELSE 0 END), 0) AS revenue_today,
                IFNULL(SUM(CASE WHEN created_at >= ?       THEN total ELSE 0 END), 0) AS revenue_this_month
            FROM orders
            WHERE status IN ('delivered', 'confirmed', 'shipped')
        ", [$today, $monthStart])->getRowArray();

        // ── Customers ────────────────────────────────────────────────────────────
        $customers = $this->db->query("
            SELECT
                COUNT(*)                                                               AS total_customers,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END)                 AS new_customers_today,
                SUM(CASE WHEN created_at >= ?       THEN 1 ELSE 0 END)                AS new_customers_this_month
            FROM users
            WHERE role = 'customer'
        ", [$today, $monthStart])->getRowArray();

        // ── Products ─────────────────────────────────────────────────────────────
        $products = $this->db->query("
            SELECT
                COUNT(*)                                   AS total_products,
                SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) AS low_stock_count
            FROM products
            WHERE is_active = 1 AND deleted_at IS NULL
        ")->getRowArray();

        // ── Pending reviews ───────────────────────────────────────────────────────
        $pendingReviews = (int) $this->db->query("
            SELECT COUNT(*) AS c FROM product_reviews WHERE is_approved = 0
        ")->getRowArray()['c'];

        // ── Recent 5 orders ───────────────────────────────────────────────────────
        $recentOrders = $this->db->query("
            SELECT o.id, o.order_number, o.status, o.total, o.created_at,
                   u.name AS customer_name, u.email AS customer_email
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            ORDER BY o.created_at DESC
            LIMIT 5
        ")->getResultArray();

        // ── Low-stock products ────────────────────────────────────────────────────
        $lowStock = $this->db->query("
            SELECT id, name, sku, stock
            FROM products
            WHERE is_active = 1 AND deleted_at IS NULL AND stock <= 5
            ORDER BY stock ASC
            LIMIT 10
        ")->getResultArray();

        return [
            'orders' => [
                'total'        => (int) $orders['total_orders'],
                'today'        => (int) $orders['orders_today'],
                'this_month'   => (int) $orders['orders_this_month'],
                'pending'      => (int) $orders['pending_orders'],
            ],
            'revenue' => [
                'total'        => (float) $revenue['total_revenue'],
                'today'        => (float) $revenue['revenue_today'],
                'this_month'   => (float) $revenue['revenue_this_month'],
            ],
            'customers' => [
                'total'        => (int) $customers['total_customers'],
                'today'        => (int) $customers['new_customers_today'],
                'this_month'   => (int) $customers['new_customers_this_month'],
            ],
            'products' => [
                'total'        => (int) $products['total_products'],
                'low_stock'    => (int) $products['low_stock_count'],
            ],
            'pending_reviews'    => $pendingReviews,
            'recent_orders'      => $recentOrders,
            'low_stock_products' => $lowStock,
        ];

        cache()->save($cacheKey, $result, 120);

        return $result;
    }

    // ─── Sales Report ──────────────────────────────────────────────────────────

    /**
     * GET /admin/reports/sales
     *
     * Daily sales breakdown for a given date range.
     *
     * Params:
     *   start_date  Y-m-d   default: first day of current month
     *   end_date    Y-m-d   default: today
     *   group_by    day|week|month  default: day
     *
     * Returns per-period: order_count, revenue, discount_given, avg_order_value
     * Plus totals for the full range.
     */
    public function getSalesReport(string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        // Clamp to reasonable range (max 366 days)
        $start = new \DateTime($startDate);
        $end   = new \DateTime($endDate);

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        $startStr = $start->format('Y-m-d');
        $endStr   = $end->format('Y-m-d');

        $dateTrunc = match ($groupBy) {
            'week'  => "DATE_FORMAT(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY), '%Y-%m-%d')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m-01')",
            default => "DATE(created_at)",
        };

        $rows = $this->db->query("
            SELECT
                {$dateTrunc}                       AS period,
                COUNT(*)                           AS order_count,
                IFNULL(SUM(total), 0)              AS revenue,
                IFNULL(SUM(discount_amount), 0)    AS discount_given,
                IFNULL(AVG(total), 0)              AS avg_order_value
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
              AND status NOT IN ('cancelled')
            GROUP BY period
            ORDER BY period ASC
        ", [$startStr, $endStr])->getResultArray();

        // Cast numeric fields
        $rows = array_map(fn($r) => [
            'period'          => $r['period'],
            'order_count'     => (int)   $r['order_count'],
            'revenue'         => (float) $r['revenue'],
            'discount_given'  => (float) $r['discount_given'],
            'avg_order_value' => round((float) $r['avg_order_value'], 2),
        ], $rows);

        // Summary totals
        $totals = $this->db->query("
            SELECT
                COUNT(*)                           AS order_count,
                IFNULL(SUM(total), 0)              AS revenue,
                IFNULL(SUM(discount_amount), 0)    AS discount_given,
                IFNULL(AVG(total), 0)              AS avg_order_value
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
              AND status NOT IN ('cancelled')
        ", [$startStr, $endStr])->getRowArray();

        return [
            'start_date'  => $startStr,
            'end_date'    => $endStr,
            'group_by'    => $groupBy,
            'totals' => [
                'order_count'     => (int)   $totals['order_count'],
                'revenue'         => (float) $totals['revenue'],
                'discount_given'  => (float) $totals['discount_given'],
                'avg_order_value' => round((float) $totals['avg_order_value'], 2),
            ],
            'data' => $rows,
        ];
    }

    // ─── Top Products Report ───────────────────────────────────────────────────

    /**
     * GET /admin/reports/top-products
     *
     * Params:
     *   limit       int     default: 10  (max 50)
     *   start_date  Y-m-d   optional
     *   end_date    Y-m-d   optional
     *   sort_by     revenue|quantity  default: revenue
     */
    public function getTopProducts(int $limit = 10, string $startDate = '', string $endDate = '', string $sortBy = 'revenue'): array
    {
        $limit  = min(50, max(1, $limit));
        $sortBy = $sortBy === 'quantity' ? 'total_quantity' : 'total_revenue';

        $where = '';
        $params = [];

        if ($startDate && $endDate) {
            $where = "AND DATE(o.created_at) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        $rows = $this->db->query("
            SELECT
                oi.product_id,
                oi.product_name,
                COUNT(DISTINCT o.id)    AS order_count,
                SUM(oi.quantity)        AS total_quantity,
                SUM(oi.subtotal)        AS total_revenue
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.status NOT IN ('cancelled')
            {$where}
            GROUP BY oi.product_id, oi.product_name
            ORDER BY {$sortBy} DESC
            LIMIT {$limit}
        ", $params)->getResultArray();

        $rows = array_map(fn($r) => [
            'product_id'     => (int)   $r['product_id'],
            'product_name'   => $r['product_name'],
            'order_count'    => (int)   $r['order_count'],
            'total_quantity' => (int)   $r['total_quantity'],
            'total_revenue'  => (float) $r['total_revenue'],
        ], $rows);

        return [
            'sort_by'    => $sortBy === 'total_quantity' ? 'quantity' : 'revenue',
            'start_date' => $startDate ?: null,
            'end_date'   => $endDate   ?: null,
            'data'       => $rows,
        ];
    }

    // ─── Order Status Breakdown ────────────────────────────────────────────────

    /**
     * GET /admin/reports/orders-by-status
     * Returns count + revenue grouped by order status.
     */
    public function getOrdersByStatus(): array
    {
        $rows = $this->db->query("
            SELECT
                status,
                COUNT(*)            AS order_count,
                IFNULL(SUM(total), 0) AS revenue
            FROM orders
            GROUP BY status
            ORDER BY order_count DESC
        ")->getResultArray();

        return array_map(fn($r) => [
            'status'      => $r['status'],
            'order_count' => (int)   $r['order_count'],
            'revenue'     => (float) $r['revenue'],
        ], $rows);
    }
}
