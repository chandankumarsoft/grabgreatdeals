<?php

namespace App\Services;

use App\Models\WishlistModel;
use App\Models\ProductModel;

class WishlistService
{
    protected WishlistModel $wishlistModel;
    protected ProductModel  $productModel;

    public function __construct()
    {
        $this->wishlistModel = new WishlistModel();
        $this->productModel  = new ProductModel();
    }

    /**
     * Get all wishlist items for a user.
     */
    public function getList(int $userId): array
    {
        return $this->wishlistModel->getByUser($userId);
    }

    /**
     * Toggle a product in the user's wishlist.
     * Returns 'added' or 'removed'.
     */
    public function toggle(int $userId, int $productId): string|false
    {
        // Verify product exists and is not deleted
        $product = $this->productModel->find($productId);
        if (! $product || $product['deleted_at'] !== null) {
            return false;
        }

        $existing = $this->wishlistModel->findByUserAndProduct($userId, $productId);

        if ($existing) {
            $this->wishlistModel->delete($existing['id']);
            return 'removed';
        }

        $this->wishlistModel->insert([
            'user_id'    => $userId,
            'product_id' => $productId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return 'added';
    }

    /**
     * Check if a specific product is in the user's wishlist.
     */
    public function isWishlisted(int $userId, int $productId): bool
    {
        return (bool) $this->wishlistModel->findByUserAndProduct($userId, $productId);
    }
}
