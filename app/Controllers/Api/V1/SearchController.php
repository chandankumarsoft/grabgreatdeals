<?php

namespace App\Controllers\Api\V1;

use App\Models\ProductModel;
use App\Models\CategoryModel;

class SearchController extends BaseApiController
{
    protected ProductModel  $productModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->productModel  = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }

    // ─── GET /search ──────────────────────────────────────────────────────────
    //
    // Query params:
    //   q          (required, min 2 chars)
    //   category   category slug filter
    //   min_price  decimal
    //   max_price  decimal
    //   limit      int (1-50, default 20)

    public function index(): \CodeIgniter\HTTP\ResponseInterface
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));

        if (strlen($q) < 2) {
            return $this->respondValidationError(['q' => 'Search query must be at least 2 characters.']);
        }

        $params = [
            'limit'     => $this->request->getGet('limit')     ?? 20,
            'category'  => (string) ($this->request->getGet('category')  ?? ''),
            'min_price' => (string) ($this->request->getGet('min_price') ?? ''),
            'max_price' => (string) ($this->request->getGet('max_price') ?? ''),
        ];

        $products   = $this->productModel->searchProducts($q, $params);
        $categories = $this->categoryModel
                           ->like('name', $q)
                           ->where('is_active', 1)
                           ->findAll(5);

        return $this->respondSuccess('Search results retrieved.', [
            'query'          => $q,
            'total_products' => count($products),
            'products'       => $products,
            'categories'     => $categories,
        ]);
    }

    // ─── GET /search/suggestions ──────────────────────────────────────────────
    //
    // Query params:
    //   q      (min 1 char)
    //   limit  int (1-10, default 8)

    public function suggestions(): \CodeIgniter\HTTP\ResponseInterface
    {
        $q     = trim((string) ($this->request->getGet('q') ?? ''));
        $limit = min(10, max(1, (int) ($this->request->getGet('limit') ?? 8)));

        if (strlen($q) < 1) {
            return $this->respondSuccess('Suggestions retrieved.', [
                'query'       => $q,
                'suggestions' => [],
            ]);
        }

        $items = $this->productModel->getSuggestions($q, $limit);

        return $this->respondSuccess('Suggestions retrieved.', [
            'query'       => $q,
            'suggestions' => $items,
        ]);
    }
}
