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

        $sortParam       = $params['sort'] ?? 'created_at';
        $needsRatingJoin = $sortParam === 'rating' || (isset($params['min_rating']) && $params['min_rating'] !== '');
        $needsPopJoin    = $sortParam === 'popularity';

        $this->select('products.*, categories.name as category_name')
             ->join('categories', 'categories.id = products.category_id', 'left')
             ->where('products.is_active', 1);

        if ($needsRatingJoin) {
            $ratingSubq = $this->db->table('product_reviews')
                ->select('product_id, AVG(rating) as avg_rating')
                ->where('is_approved', 1)
                ->groupBy('product_id')
                ->getCompiledSelect();
            $this->join("($ratingSubq) pr", 'pr.product_id = products.id', 'left');
        }

        if ($needsPopJoin) {
            $popSubq = $this->db->table('order_items')
                ->select('order_items.product_id, SUM(order_items.quantity) as total_sold')
                ->join('orders', 'orders.id = order_items.order_id')
                ->where('orders.status !=', 'cancelled')
                ->groupBy('order_items.product_id')
                ->getCompiledSelect();
            $this->join("($popSubq) ps", 'ps.product_id = products.id', 'left');
        }

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

        if (! empty($params['in_stock'])) {
            $this->where('products.stock >', 0);
        }

        if (! empty($params['on_sale'])) {
            $this->where('products.sale_price IS NOT NULL')
                 ->where('products.sale_price >', 0);
        }

        if ($needsRatingJoin && isset($params['min_rating']) && $params['min_rating'] !== '') {
            $minRating = (float) $params['min_rating'];
            $this->where("IFNULL(pr.avg_rating, 0) >= $minRating");
        }

        $order = strtoupper($params['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        if ($sortParam === 'rating') {
            $this->orderBy("IFNULL(pr.avg_rating, 0) $order");
        } elseif ($sortParam === 'popularity') {
            $this->orderBy("IFNULL(ps.total_sold, 0) $order");
        } elseif ($sortParam === 'price') {
            $this->orderBy('products.price', $order);
        } elseif ($sortParam === 'name') {
            $this->orderBy('products.name', $order);
        } else {
            $this->orderBy('products.created_at', $order);
        }

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
                 ->orLike('products.description', $params['search'])
                 ->groupEnd();
        }

        if (! empty($params['category'])) {
            $this->where('categories.slug', $params['category']);
        }

        if (isset($params['is_active']) && $params['is_active'] !== '') {
            $this->where('products.is_active', (int) $params['is_active']);
        }

        if (isset($params['is_featured']) && $params['is_featured'] !== '') {
            $this->where('products.is_featured', (int) $params['is_featured']);
        }

        if (isset($params['min_price']) && $params['min_price'] !== '') {
            $this->where('products.price >=', (float) $params['min_price']);
        }

        if (isset($params['max_price']) && $params['max_price'] !== '') {
            $this->where('products.price <=', (float) $params['max_price']);
        }

        if (isset($params['low_stock']) && $params['low_stock'] !== '') {
            $this->where('products.stock <=', max(1, (int) $params['low_stock']));
        }

        $allowedSorts = ['price', 'name', 'stock', 'created_at'];
        $sortParam    = in_array($params['sort'] ?? '', $allowedSorts) ? 'products.' . $params['sort'] : 'products.created_at';
        $order        = strtoupper($params['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $this->orderBy($sortParam, $order);

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

    // ─── Search ───────────────────────────────────────────────────────────────

    /**
     * Relevance-ranked product search.
     * Scores: exact name match = 4, starts-with = 3, name contains = 2, other = 1.
     */
    public function searchProducts(string $query, array $params = []): array
    {
        $limit    = min(50, max(1, (int) ($params['limit'] ?? 20)));
        $category = $params['category'] ?? '';

        $bindParams = [
            $query,              // CASE: exact match
            $query . '%',        // CASE: starts-with
            '%' . $query . '%',  // CASE: contains
            '%' . $query . '%',  // WHERE name LIKE
            '%' . $query . '%',  // WHERE description LIKE
            '%' . $query . '%',  // WHERE sku LIKE
        ];

        $categoryWhere = '';
        if ($category !== '') {
            $categoryWhere = 'AND categories.slug = ?';
            $bindParams[]  = $category;
        }

        $priceWhere = '';
        if (isset($params['min_price']) && $params['min_price'] !== '') {
            $priceWhere   .= ' AND products.price >= ?';
            $bindParams[]  = (float) $params['min_price'];
        }
        if (isset($params['max_price']) && $params['max_price'] !== '') {
            $priceWhere   .= ' AND products.price <= ?';
            $bindParams[]  = (float) $params['max_price'];
        }

        $sql = "
            SELECT products.*, categories.name AS category_name,
                CASE
                    WHEN products.name = ?         THEN 4
                    WHEN products.name LIKE ?      THEN 3
                    WHEN products.name LIKE ?      THEN 2
                    ELSE 1
                END AS relevance
            FROM products
            LEFT JOIN categories ON categories.id = products.category_id
            WHERE products.is_active = 1
              AND products.deleted_at IS NULL
              AND (
                    products.name        LIKE ?
                 OR products.description LIKE ?
                 OR products.sku         LIKE ?
              )
              {$categoryWhere}
              {$priceWhere}
            ORDER BY relevance DESC, products.name ASC
            LIMIT {$limit}
        ";

        return $this->db->query($sql, $bindParams)->getResultArray();
    }

    /**
     * Quick autocomplete suggestions — up to $limit product names matching $query.
     */
    public function getSuggestions(string $query, int $limit = 8): array
    {
        return $this->select('id, name, slug, thumbnail, price, sale_price')
                    ->where('is_active', 1)
                    ->like('name', $query)
                    ->orderBy('name', 'ASC')
                    ->findAll($limit);
    }
}
