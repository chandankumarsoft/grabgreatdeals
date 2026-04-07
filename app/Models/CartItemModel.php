<?php

namespace App\Models;

use CodeIgniter\Model;

class CartItemModel extends Model
{
    protected $table         = 'cart_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByCart(int $cartId): array
    {
        return $this->select('cart_items.*, products.name as product_name, products.thumbnail, products.stock as product_stock, product_variants.name as variant_name, product_variants.value as variant_value')
                    ->join('products', 'products.id = cart_items.product_id', 'left')
                    ->join('product_variants', 'product_variants.id = cart_items.variant_id', 'left')
                    ->where('cart_items.cart_id', $cartId)
                    ->orderBy('cart_items.created_at', 'ASC')
                    ->findAll();
    }

    public function findDuplicate(int $cartId, int $productId, ?int $variantId): ?array
    {
        $builder = $this->where('cart_id', $cartId)->where('product_id', $productId);

        if ($variantId) {
            $builder->where('variant_id', $variantId);
        } else {
            $builder->where('variant_id IS NULL', null, false);
        }

        return $builder->first();
    }

    public function getByCartAndId(int $cartId, int $itemId): ?array
    {
        return $this->where('cart_id', $cartId)->where('id', $itemId)->first();
    }

    public function clearCart(int $cartId): void
    {
        $this->where('cart_id', $cartId)->delete();
    }
}
