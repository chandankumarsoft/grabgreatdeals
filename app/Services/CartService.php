<?php

namespace App\Services;

use App\Models\CartModel;
use App\Models\CartItemModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;

class CartService
{
    protected CartModel           $cartModel;
    protected CartItemModel       $itemModel;
    protected ProductModel        $productModel;
    protected ProductVariantModel $variantModel;

    public function __construct()
    {
        $this->cartModel    = new CartModel();
        $this->itemModel    = new CartItemModel();
        $this->productModel = new ProductModel();
        $this->variantModel = new ProductVariantModel();
    }

    public function getCart(int $userId): array
    {
        $cart  = $this->cartModel->getOrCreateForUser($userId);
        $items = $this->itemModel->getByCart((int) $cart['id']);

        return [
            'cart_id'     => $cart['id'],
            'items'       => $items,
            'item_count'  => count($items),
            'total'       => $this->calculateTotal($items),
        ];
    }

    public function addItem(int $userId, array $data): array|string
    {
        $productId = (int) $data['product_id'];
        $variantId = isset($data['variant_id']) ? (int) $data['variant_id'] : null;
        $quantity  = max(1, (int) ($data['quantity'] ?? 1));

        $product = $this->productModel->find($productId);

        if (! $product || ! $product['is_active']) {
            return 'product_not_found';
        }

        $unitPrice = $product['sale_price'] ?? $product['price'];

        if ($variantId) {
            $variant = $this->variantModel->find($variantId);

            if (! $variant || (int) $variant['product_id'] !== $productId || ! $variant['is_active']) {
                return 'variant_not_found';
            }

            $unitPrice = (float) $unitPrice + (float) $variant['price_adjustment'];
        }

        $availableStock = $variantId
            ? ($variant['stock'] ?? 0)
            : (int) $product['stock'];

        if ($quantity > $availableStock) {
            return 'insufficient_stock';
        }

        $cart      = $this->cartModel->getOrCreateForUser($userId);
        $cartId    = (int) $cart['id'];
        $duplicate = $this->itemModel->findDuplicate($cartId, $productId, $variantId);

        if ($duplicate) {
            $newQty = (int) $duplicate['quantity'] + $quantity;

            if ($newQty > $availableStock) {
                return 'insufficient_stock';
            }

            $this->itemModel->update($duplicate['id'], ['quantity' => $newQty]);
        } else {
            $this->itemModel->insert([
                'cart_id'    => $cartId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
            ]);
        }

        return $this->getCart($userId);
    }

    public function updateItem(int $userId, int $itemId, int $quantity): array|string
    {
        $cart = $this->cartModel->getOrCreateForUser($userId);
        $item = $this->itemModel->getByCartAndId((int) $cart['id'], $itemId);

        if (! $item) {
            return 'not_found';
        }

        $product = $this->productModel->find($item['product_id']);
        $stock   = $item['variant_id']
            ? ($this->variantModel->find($item['variant_id'])['stock'] ?? 0)
            : (int) $product['stock'];

        if ($quantity > $stock) {
            return 'insufficient_stock';
        }

        $this->itemModel->update($itemId, ['quantity' => $quantity]);

        return $this->getCart($userId);
    }

    public function removeItem(int $userId, int $itemId): array|string
    {
        $cart = $this->cartModel->getOrCreateForUser($userId);
        $item = $this->itemModel->getByCartAndId((int) $cart['id'], $itemId);

        if (! $item) {
            return 'not_found';
        }

        $this->itemModel->delete($itemId);

        return $this->getCart($userId);
    }

    public function clearCart(int $userId): bool
    {
        $cart = $this->cartModel->getOrCreateForUser($userId);
        $this->itemModel->clearCart((int) $cart['id']);

        return true;
    }

    private function calculateTotal(array $items): float
    {
        return array_reduce($items, function (float $carry, array $item): float {
            return $carry + ((float) $item['unit_price'] * (int) $item['quantity']);
        }, 0.0);
    }
}
