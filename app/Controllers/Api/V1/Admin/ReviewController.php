<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;
use App\Services\ReviewService;

class ReviewController extends BaseApiController
{
    protected ReviewService $reviewService;

    public function __construct()
    {
        $this->reviewService = new ReviewService();
    }

    /**
     * GET /admin/reviews
     * Paginated list of all reviews.
     * Query: status=pending|approved
     */
    public function index()
    {
        $page    = max(1, (int) ($this->request->getGet('page')     ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 15)));
        $status  = $this->request->getGet('status') ?? '';

        $result = $this->reviewService->adminList($perPage, $page, $status);

        return $this->respondSuccess('Reviews retrieved', $result);
    }

    /**
     * PUT /admin/reviews/{id}/approve
     * Body: { "is_approved": 1|0 }
     */
    public function approve(int $id)
    {
        $rules = ['is_approved' => 'required|in_list[0,1]'];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $isApproved = (int) $this->request->getJSON()->is_approved;
        $result     = $this->reviewService->adminApprove($id, $isApproved);

        if ($result === false) {
            return $this->respondNotFound('Review not found');
        }

        $msg = $isApproved ? 'Review approved' : 'Review hidden';

        return $this->respondSuccess($msg, $result);
    }

    /**
     * DELETE /admin/reviews/{id}
     */
    public function delete(int $id)
    {
        if (! $this->reviewService->adminDelete($id)) {
            return $this->respondNotFound('Review not found');
        }

        return $this->respondSuccess('Review deleted');
    }
}
