<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table          = 'products';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'stock',
        'sku',
        'thumbnail',
        'is_active',
        'is_featured',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findBySlug(string $slug): ?array
    {
        return $this->select('products.*, categories.name as category_name, categories.slug as category_slug')
                    ->join('categories', 'categories.id = products.category_id', 'left')
                    ->where('products.slug', $slug)
                    ->where('products.is_active', 1)
                    ->first();
    }

    public function getProductList(array $params = []): array
    {
        $perPage = max(1, min(100, (int) ($params['per_page'] ?? 15)));
        $page    = max(1, (int) ($params['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $this->select('products.*, categories.name as category_name')
             ->join('categories', 'categories.id = products.category_id', 'left')
             ->where('products.is_active', 1);

        if (! empty($params['category'])) {
            $this->where('categories.slug', $params['category']);
        }

        if (! empty($params['search'])) {
            $this->groupStart()
                 ->like('products.name', $params['search'])
                 ->orLike('products.description', $params['search'])
                 ->groupEnd();
        }

        if (! empty($params['featured'])) {
            $this->where('products.is_featured', 1);
        }

        if (isset($params['min_price']) && $params['min_price'] !== '') {
            $this->where('products.price >=', (float) $params['min_price']);
        }

        if (isset($params['max_price']) && $params['max_price'] !== '') {
            $this->where('products.price <=', (float) $params['max_price']);
        }

        $allowedSorts = ['price', 'name', 'created_at'];
        $sort  = in_array($params['sort'] ?? '', $allowedSorts) ? 'products.' . $params['sort'] : 'products.created_at';
        $order = strtoupper($params['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $this->orderBy($sort, $order);

        $total = $this->countAllResults(false);
        $items = $this->findAll($perPage, $offset);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function getAdminList(array $params = []): array
    {
        $perPage = max(1, min(100, (int) ($params['per_page'] ?? 15)));
        $page    = max(1, (int) ($params['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $this->select('products.*, categories.name as category_name')
             ->join('categories', 'categories.id = products.category_id', 'left');

        if (! empty($params['search'])) {
            $this->groupStart()
                 ->like('products.name', $params['search'])
                 ->orLike('products.sku', $params['search'])
                 ->groupEnd();
        }

        $this->orderBy('products.created_at', 'DESC');

        $total = $this->countAllResults(false);
        $items = $this->findAll($perPage, $offset);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $builder = $this->where('slug', $slug);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return (bool) $builder->countAllResults();
    }
}
