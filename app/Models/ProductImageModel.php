<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductImageModel extends Model
{
    protected $table         = 'product_images';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'product_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';

    public function getByProduct(int $productId): array
    {
        return $this->where('product_id', $productId)->orderBy('sort_order', 'ASC')->orderBy('is_primary', 'DESC')->findAll();
    }

    public function getByProductAndId(int $productId, int $imageId): ?array
    {
        return $this->where('product_id', $productId)->where('id', $imageId)->first();
    }

    public function clearPrimary(int $productId): void
    {
        $this->where('product_id', $productId)->set('is_primary', 0)->update();
    }

    public function deleteByProduct(int $productId): void
    {
        $this->where('product_id', $productId)->delete();
    }
}
