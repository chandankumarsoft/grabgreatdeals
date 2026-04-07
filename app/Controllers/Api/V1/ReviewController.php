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
        $page    = max(1, (int) ($this->request->getGet('page')     ?? 1));
        $perPage = min(50, max(1, (int) ($this->request->getGet('per_page') ?? 10)));

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
        $rules = [
            'rating' => 'required|integer|greater_than[0]|less_than[6]',
            'title'  => 'permit_empty|max_length[150]',
            'body'   => 'permit_empty|max_length[2000]',
        ];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
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

        return $this->respondSuccess('Review submitted', $result, 201);
    }

    /**
     * PUT /products/{productId}/reviews/{reviewId}
     * Authenticated: edit own review.
     */
    public function update(int $productId, int $reviewId)
    {
        $rules = [
            'rating' => 'permit_empty|integer|greater_than[0]|less_than[6]',
            'title'  => 'permit_empty|max_length[150]',
            'body'   => 'permit_empty|max_length[2000]',
        ];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
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
