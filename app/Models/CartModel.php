<?php

namespace App\Models;

use CodeIgniter\Model;

class CartModel extends Model
{
    protected $table         = 'carts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['user_id'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getOrCreateForUser(int $userId): array
    {
        $cart = $this->where('user_id', $userId)->first();

        if (! $cart) {
            $cartId = $this->insert(['user_id' => $userId]);
            $cart   = $this->find($cartId);
        }

        return $cart;
    }
}
