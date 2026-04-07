<?php

namespace App\Controllers\Api\V1;

use App\Services\WishlistService;

class WishlistController extends BaseApiController
{
    protected WishlistService $wishlistService;

    public function __construct()
    {
        $this->wishlistService = new WishlistService();
    }

    /**
     * GET /wishlist
     * Returns all wishlist items for the authenticated user.
     */
    public function index()
    {
        $userId = (int) $this->request->jwtPayload->sub;
        $items  = $this->wishlistService->getList($userId);

        return $this->respondSuccess('Wishlist retrieved', [
            'items' => $items,
            'count' => count($items),
        ]);
    }

    /**
     * POST /wishlist/{productId}
     * Toggle a product in/out of the wishlist.
     * Returns the new state: 'added' or 'removed'.
     */
    public function toggle(int $productId)
    {
        $userId = (int) $this->request->jwtPayload->sub;
        $result = $this->wishlistService->toggle($userId, $productId);

        if ($result === false) {
            return $this->respondNotFound('Product not found');
        }

        $message = $result === 'added' ? 'Product added to wishlist' : 'Product removed from wishlist';

        return $this->respondSuccess($message, ['action' => $result, 'product_id' => $productId]);
    }
}
