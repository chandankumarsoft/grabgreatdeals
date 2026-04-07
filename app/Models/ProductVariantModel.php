<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table         = 'product_variants';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'product_id',
        'name',
        'value',
        'price_adjustment',
        'stock',
        'sku',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByProduct(int $productId): array
    {
        return $this->where('product_id', $productId)->where('is_active', 1)->findAll();
    }

    public function deleteByProduct(int $productId): void
    {
        $this->where('product_id', $productId)->delete();
    }
}
