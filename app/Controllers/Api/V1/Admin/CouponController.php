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
        if (! $this->validate('admin_coupon_create')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true);

        // percent value must be 1–100–100
        if ($data['type'] === 'percent' && (float) $data['value'] > 100) {
            return $this->respondValidationErrors(['value' => 'Percent discount cannot exceed 100.']);
        }

        $coupon = $this->couponService->create($data);

        return $this->respondCreated('Coupon created', $coupon);
    }

    /**
     * PUT /admin/coupons/{id}
     */
    public function update(int $id)
    {
        if (! $this->validate('admin_coupon_update')) {
            return $this->respondValidationErrors($this->validator->getErrors());
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
