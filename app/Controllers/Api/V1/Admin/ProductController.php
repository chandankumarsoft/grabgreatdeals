<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;
use App\Services\ProductService;

class ProductController extends BaseApiController
{
    protected ProductService $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    public function index()
    {
        $params = [
            'page'        => $this->request->getGet('page'),
            'per_page'    => $this->request->getGet('per_page'),
            'search'      => $this->request->getGet('search'),
            'category'    => $this->request->getGet('category'),
            'is_active'   => $this->request->getGet('is_active'),
            'is_featured' => $this->request->getGet('is_featured'),
            'min_price'   => $this->request->getGet('min_price'),
            'max_price'   => $this->request->getGet('max_price'),
            'low_stock'   => $this->request->getGet('low_stock'),
            'sort'        => $this->request->getGet('sort'),
            'order'       => $this->request->getGet('order'),
        ];

        $result = $this->productService->getAdminProducts($params);

        return $this->respondSuccess('Products retrieved', $result);
    }

    public function create()
    {
        if (! $this->validate('admin_product_create')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $result = $this->productService->create($this->request->getJSON(true));

        if (! $result) {
            return $this->respondError('Failed to create product', $this->productService->getValidationErrors(), 422);
        }

        return $this->respondCreated('Product created', $result);
    }

    public function update(int $id)
    {
        $rules = config('Validation')->admin_product_update;
        $rules['sku'] = "permit_empty|max_length[100]|is_unique[products.sku,id,{$id}]";

        if (! $this->validate($rules)) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $result = $this->productService->update($id, $this->request->getJSON(true));

        if ($result === false) {
            return $this->respondNotFound('Product not found');
        }

        return $this->respondSuccess('Product updated', $result);
    }

    public function delete(int $id)
    {
        $deleted = $this->productService->delete($id);

        if (! $deleted) {
            return $this->respondNotFound('Product not found');
        }

        return $this->respondSuccess('Product deleted');
    }

    public function uploadImages(int $id)
    {
        $files = $this->request->getFileMultiple('images') ?? [];

        if (empty($files)) {
            $single = $this->request->getFile('images');
            if ($single) {
                $files = [$single];
            }
        }

        if (empty($files)) {
            return $this->respondError('No image files provided');
        }

        $isPrimary = (bool) $this->request->getPost('is_primary');
        $result    = $this->productService->uploadImages($id, $files, $isPrimary);

        if (empty($result)) {
            return $this->respondError('No valid images were uploaded. Allowed: jpeg, png, webp, gif');
        }

        return $this->respondCreated('Images uploaded', $result);
    }

    public function deleteImage(int $productId, int $imageId)
    {
        $deleted = $this->productService->deleteImage($productId, $imageId);

        if (! $deleted) {
            return $this->respondNotFound('Image not found');
        }

        return $this->respondSuccess('Image deleted');
    }
}
