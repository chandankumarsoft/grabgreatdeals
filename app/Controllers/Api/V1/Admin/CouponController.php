<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;
use App\Services\CouponService;

class CouponController extends BaseApiController
{
    protected CouponService $couponService;

    public function __construct()
    {
        $this->couponService = new CouponService();
    }

    /**
     * GET /admin/coupons
     */
    public function index()
    {
        $page    = max(1, (int) ($this->request->getGet('page')     ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 15)));

        return $this->respondSuccess('Coupons retrieved', $this->couponService->list($perPage, $page));
    }

    /**
     * GET /admin/coupons/{id}
     */
    public function show(int $id)
    {
        $coupon = $this->couponService->getById($id);

        if (! $coupon) {
            return $this->respondNotFound('Coupon not found');
        }

        return $this->respondSuccess('Coupon retrieved', $coupon);
    }

    /**
     * POST /admin/coupons
     */
    public function create()
    {
        $rules = [
            'code'                 => 'required|max_length[50]',
            'type'                 => 'required|in_list[percent,fixed]',
            'value'                => 'required|decimal|greater_than[0]',
            'description'          => 'permit_empty|max_length[255]',
            'min_order_amount'     => 'permit_empty|decimal|greater_than_equal_to[0]',
            'max_discount_amount'  => 'permit_empty|decimal|greater_than[0]',
            'usage_limit'          => 'permit_empty|integer|greater_than[0]',
            'usage_limit_per_user' => 'permit_empty|integer|greater_than[0]',
            'is_active'            => 'permit_empty|in_list[0,1]',
            'starts_at'            => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'expires_at'           => 'permit_empty|valid_date[Y-m-d H:i:s]',
        ];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);

        // percent value must be 1–100
        if ($data['type'] === 'percent' && (float) $data['value'] > 100) {
            return $this->respondValidationError(['value' => 'Percent discount cannot exceed 100.']);
        }

        $coupon = $this->couponService->create($data);

        return $this->respondSuccess('Coupon created', $coupon, 201);
    }

    /**
     * PUT /admin/coupons/{id}
     */
    public function update(int $id)
    {
        $rules = [
            'code'                 => 'permit_empty|max_length[50]',
            'type'                 => 'permit_empty|in_list[percent,fixed]',
            'value'                => 'permit_empty|decimal|greater_than[0]',
            'description'          => 'permit_empty|max_length[255]',
            'min_order_amount'     => 'permit_empty|decimal|greater_than_equal_to[0]',
            'max_discount_amount'  => 'permit_empty|decimal|greater_than[0]',
            'usage_limit'          => 'permit_empty|integer|greater_than[0]',
            'usage_limit_per_user' => 'permit_empty|integer|greater_than[0]',
            'is_active'            => 'permit_empty|in_list[0,1]',
            'starts_at'            => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'expires_at'           => 'permit_empty|valid_date[Y-m-d H:i:s]',
        ];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $data   = $this->request->getJSON(true);
        $result = $this->couponService->update($id, $data);

        if ($result === false) {
            return $this->respondNotFound('Coupon not found');
        }

        return $this->respondSuccess('Coupon updated', $result);
    }

    /**
     * DELETE /admin/coupons/{id}
     */
    public function delete(int $id)
    {
        if (! $this->couponService->delete($id)) {
            return $this->respondNotFound('Coupon not found');
        }

        return $this->respondSuccess('Coupon deleted');
    }
}
