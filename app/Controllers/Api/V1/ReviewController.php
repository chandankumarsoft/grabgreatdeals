<?php

namespace App\Controllers\Api\V1;

use App\Services\ReviewService;

class ReviewController extends BaseApiController
{
    protected ReviewService $reviewService;

    public function __construct()
    {
        $this->reviewService = new ReviewService();
    }

    /**
     * GET /products/{productId}/reviews
     * Public: list approved reviews for a product.
     */
    public function index(int $productId)
    {
        ['page' => $page, 'per_page' => $perPage] = $this->getPaginationParams(10, 50);

        $result = $this->reviewService->getForProduct($productId, $perPage, $page);
        $stats  = $this->reviewService->getRatingStats($productId);

        return $this->respondSuccess('Reviews retrieved', array_merge($stats, $result));
    }

    /**
     * POST /products/{productId}/reviews
     * Authenticated: submit a review (must have delivered order with this product).
     */
    public function create(int $productId)
    {
        if (! $this->validate('review_create')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $userId = (int) $this->getAuthUserId();
        $result = $this->reviewService->submit($userId, $productId, $this->request->getJSON(true));

        if ($result === 'product_not_found') {
            return $this->respondNotFound('Product not found');
        }

        if ($result === 'purchase_required') {
            return $this->respondError('You can only review products you have purchased and received.', [], 422);
        }

        if ($result === 'already_reviewed') {
            return $this->respondError('You have already submitted a review for this product.', [], 422);
        }

        return $this->respondCreated('Review submitted', $result);
    }

    /**
     * PUT /products/{productId}/reviews/{reviewId}
     * Authenticated: edit own review.
     */
    public function update(int $productId, int $reviewId)
    {
        if (! $this->validate('review_update')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $userId = (int) $this->getAuthUserId();
        $result = $this->reviewService->update($userId, $reviewId, $this->request->getJSON(true));

        if ($result === false) {
            return $this->respondNotFound('Review not found');
        }

        return $this->respondSuccess('Review updated', $result);
    }

    /**
     * DELETE /products/{productId}/reviews/{reviewId}
     * Authenticated: delete own review.
     */
    public function delete(int $productId, int $reviewId)
    {
        $userId = (int) $this->getAuthUserId();

        if (! $this->reviewService->delete($userId, $reviewId)) {
            return $this->respondNotFound('Review not found');
        }

        return $this->respondSuccess('Review deleted');
    }
}
