<?php

namespace App\Controllers\Api\V1;

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
            'page'      => $this->request->getGet('page'),
            'per_page'  => $this->request->getGet('per_page'),
            'category'  => $this->request->getGet('category'),
            'search'    => $this->request->getGet('search'),
            'featured'  => $this->request->getGet('featured'),
            'min_price' => $this->request->getGet('min_price'),
            'max_price' => $this->request->getGet('max_price'),
            'sort'      => $this->request->getGet('sort'),
            'order'     => $this->request->getGet('order'),
        ];

        $result = $this->productService->getProducts($params);

        return $this->respondSuccess('Products retrieved', $result);
    }

    public function show(string $slug)
    {
        $product = $this->productService->getProductBySlug($slug);

        if (! $product) {
            return $this->respondNotFound('Product not found');
        }

        return $this->respondSuccess('Product retrieved', $product);
    }
}
