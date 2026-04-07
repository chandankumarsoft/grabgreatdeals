<?php

namespace App\Services;

use App\Models\ReviewModel;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ProductModel;

class ReviewService
{
    protected ReviewModel     $reviewModel;
    protected OrderModel      $orderModel;
    protected OrderItemModel  $orderItemModel;
    protected ProductModel    $productModel;

    public function __construct()
    {
        $this->reviewModel    = new ReviewModel();
        $this->orderModel     = new OrderModel();
        $this->orderItemModel = new OrderItemModel();
        $this->productModel   = new ProductModel();
    }

    /**
     * Submit a new review.
     *
     * Rules:
     * - Product must exist
     * - User must have a delivered order containing the product
     * - User may only submit one review per product
     *
     * Returns the review array or an error string.
     */
    public function submit(int $userId, int $productId, array $data): array|string
    {
        // Verify product exists
        $product = $this->productModel->find($productId);
        if (! $product) {
            return 'product_not_found';
        }

        // Verify user has a delivered order containing this product
        $deliveredOrderId = $this->findDeliveredOrderWithProduct($userId, $productId);
        if (! $deliveredOrderId) {
            return 'purchase_required';
        }

        // Check for duplicate review
        if ($this->reviewModel->findByUserAndProduct($userId, $productId)) {
            return 'already_reviewed';
        }

        $id = $this->reviewModel->insert([
            'product_id'  => $productId,
            'user_id'     => $userId,
            'order_id'    => $deliveredOrderId,
            'rating'      => (int) $data['rating'],
            'title'       => $data['title'] ?? null,
            'body'        => $data['body']  ?? null,
            'is_approved' => 1,
        ], true);

        return $this->reviewModel->find($id);
    }

    /**
     * Update the authenticated user's own review.
     */
    public function update(int $userId, int $reviewId, array $data): array|false
    {
        $review = $this->reviewModel
            ->where('id', $reviewId)
            ->where('user_id', $userId)
            ->first();

        if (! $review) {
            return false;
        }

        $update = [];

        if (isset($data['rating'])) {
            $update['rating'] = (int) $data['rating'];
        }
        if (array_key_exists('title', $data)) {
            $update['title'] = $data['title'];
        }
        if (array_key_exists('body', $data)) {
            $update['body'] = $data['body'];
        }

        if (! empty($update)) {
            $this->reviewModel->update($reviewId, $update);
        }

        return $this->reviewModel->find($reviewId);
    }

    /**
     * Delete a user's own review.
     */
    public function delete(int $userId, int $reviewId): bool
    {
        $review = $this->reviewModel
            ->where('id', $reviewId)
            ->where('user_id', $userId)
            ->first();

        if (! $review) {
            return false;
        }

        $this->reviewModel->delete($reviewId);

        return true;
    }

    /**
     * Public: get approved reviews for a product.
     */
    public function getForProduct(int $productId, int $perPage = 10, int $page = 1): array
    {
        return $this->reviewModel->getByProduct($productId, true, $perPage, $page);
    }

    /**
     * Get rating stats (avg + count) for a product.
     */
    public function getRatingStats(int $productId): array
    {
        return $this->reviewModel->getRatingStats($productId);
    }

    // ─── Admin ─────────────────────────────────────────────────────────────────

    public function adminList(int $perPage = 15, int $page = 1, string $status = ''): array
    {
        return $this->reviewModel->getAdminList($perPage, $page, $status);
    }

    public function adminApprove(int $reviewId, int $isApproved): array|false
    {
        $review = $this->reviewModel->find($reviewId);
        if (! $review) {
            return false;
        }

        $this->reviewModel->update($reviewId, ['is_approved' => $isApproved]);

        return $this->reviewModel->find($reviewId);
    }

    public function adminDelete(int $reviewId): bool
    {
        if (! $this->reviewModel->find($reviewId)) {
            return false;
        }

        $this->reviewModel->delete($reviewId);

        return true;
    }

    // ─── Private helpers ───────────────────────────────────────────────────────

    private function findDeliveredOrderWithProduct(int $userId, int $productId): ?int
    {
        $row = $this->orderModel
            ->select('orders.id')
            ->join('order_items', 'order_items.order_id = orders.id')
            ->where('orders.user_id', $userId)
            ->where('orders.status', 'delivered')
            ->where('order_items.product_id', $productId)
            ->first();

        return $row ? (int) $row['id'] : null;
    }
}
