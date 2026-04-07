<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewModel extends Model
{
    protected $table         = 'product_reviews';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'title',
        'body',
        'is_approved',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Paginated reviews for a product (only approved ones for public view).
     */
    public function getByProduct(int $productId, bool $approvedOnly = true, int $perPage = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;

        $this->select('product_reviews.*, users.name as reviewer_name')
             ->join('users', 'users.id = product_reviews.user_id', 'left')
             ->where('product_reviews.product_id', $productId);

        if ($approvedOnly) {
            $this->where('product_reviews.is_approved', 1);
        }

        $total = $this->countAllResults(false);
        $items = $this->orderBy('product_reviews.created_at', 'DESC')
                      ->findAll($perPage, $offset);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    /**
     * Aggregate rating stats for a product.
     * Returns ['avg_rating' => float, 'review_count' => int]
     */
    public function getRatingStats(int $productId): array
    {
        $row = $this->selectAvg('rating', 'avg_rating')
                    ->selectCount('id', 'review_count')
                    ->where('product_id', $productId)
                    ->where('is_approved', 1)
                    ->first();

        return [
            'avg_rating'   => $row['avg_rating'] ? round((float) $row['avg_rating'], 1) : null,
            'review_count' => (int) ($row['review_count'] ?? 0),
        ];
    }

    /**
     * Find a user's existing review for a product.
     */
    public function findByUserAndProduct(int $userId, int $productId): ?array
    {
        return $this->where('user_id', $userId)->where('product_id', $productId)->first();
    }

    /**
     * Admin: paginated list of all reviews with product + user info.
     */
    public function getAdminList(int $perPage = 15, int $page = 1, string $status = ''): array
    {
        $offset = ($page - 1) * $perPage;

        $this->select('product_reviews.*, users.name as reviewer_name, users.email as reviewer_email, products.name as product_name')
             ->join('users', 'users.id = product_reviews.user_id', 'left')
             ->join('products', 'products.id = product_reviews.product_id', 'left');

        if ($status === 'pending') {
            $this->where('product_reviews.is_approved', 0);
        } elseif ($status === 'approved') {
            $this->where('product_reviews.is_approved', 1);
        }

        $this->orderBy('product_reviews.created_at', 'DESC');

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
}
