<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table         = 'categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('sort_order', 'ASC')->findAll();
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->where('is_active', 1)->first();
    }
}
