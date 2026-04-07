<?php

namespace App\Controllers\Api\V1;

use App\Services\ProductService;

class SearchController extends BaseApiController
{
    protected ProductService $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    // â”€â”€â”€ GET /search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
            return $this->respondValidationErrors(['q' => 'Search query must be at least 2 characters.']);
        }

        $params = [
            'limit'     => $this->request->getGet('limit')    ?? 20,
            'category'  => (string) ($this->request->getGet('category')  ?? ''),
            'min_price' => (string) ($this->request->getGet('min_price') ?? ''),
            'max_price' => (string) ($this->request->getGet('max_price') ?? ''),
        ];

        return $this->respondSuccess('Search results retrieved.', $this->productService->search($q, $params));
    }

    // â”€â”€â”€ GET /search/suggestions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

        return $this->respondSuccess('Suggestions retrieved.', [
            'query'       => $q,
            'suggestions' => $this->productService->getSuggestions($q, $limit),
        ]);
    }
}
