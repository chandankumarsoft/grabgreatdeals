<?php

namespace App\Controllers\Api\V1;

use App\Services\CartService;

class CartController extends BaseApiController
{
    protected CartService $cartService;

    public function __construct()
    {
        $this->cartService = new CartService();
    }

    public function index()
    {
        $userId = (int) $this->getAuthUserId();
        $cart   = $this->cartService->getCart($userId);

        return $this->respondSuccess('Cart retrieved', $cart);
    }

    public function add()
    {
        $rules = [
            'product_id' => 'required|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than[0]',
            'quantity'   => 'permit_empty|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $userId = (int) $this->getAuthUserId();
        $result = $this->cartService->addItem($userId, $this->request->getJSON(true));

        return $this->handleServiceResult($result, 'Item added to cart');
    }

    public function update(int $itemId)
    {
        $rules = [
            'quantity' => 'required|integer|greater_than[0]',
        ];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $userId   = (int) $this->getAuthUserId();
        $quantity = (int) $this->request->getJSON(true)['quantity'];
        $result   = $this->cartService->updateItem($userId, $itemId, $quantity);

        return $this->handleServiceResult($result, 'Cart item updated');
    }

    public function remove(int $itemId)
    {
        $userId = (int) $this->getAuthUserId();
        $result = $this->cartService->removeItem($userId, $itemId);

        return $this->handleServiceResult($result, 'Item removed from cart');
    }

    public function clear()
    {
        $userId = (int) $this->getAuthUserId();
        $this->cartService->clearCart($userId);

        return $this->respondSuccess('Cart cleared');
    }

    private function handleServiceResult(mixed $result, string $successMessage)
    {
        return match ($result) {
            'product_not_found'  => $this->respondNotFound('Product not found or inactive'),
            'variant_not_found'  => $this->respondNotFound('Variant not found or does not belong to this product'),
            'insufficient_stock' => $this->respondError('Requested quantity exceeds available stock', [], 422),
            'not_found'          => $this->respondNotFound('Cart item not found'),
            default              => $this->respondSuccess($successMessage, $result),
        };
    }
}
