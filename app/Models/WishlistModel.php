<?php

namespace App\Models;

use CodeIgniter\Model;

class WishlistModel extends Model
{
    protected $table         = 'wishlists';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'user_id',
        'product_id',
        'created_at',
    ];

    /**
     * Get the full wishlist for a user, joined with product details.
     */
    public function getByUser(int $userId): array
    {
        return $this->select(
                'wishlists.id,
                 wishlists.created_at as added_at,
                 products.id as product_id,
                 products.name as product_name,
                 products.slug,
                 products.price,
                 products.sale_price,
                 products.stock'
            )
            ->join('products', 'products.id = wishlists.product_id', 'left')
            ->where('wishlists.user_id', $userId)
            ->where('products.deleted_at IS NULL')
            ->orderBy('wishlists.created_at', 'DESC')
            ->findAll();
    }

    public function findByUserAndProduct(int $userId, int $productId): ?array
    {
        return $this->where('user_id', $userId)->where('product_id', $productId)->first();
    }
}
