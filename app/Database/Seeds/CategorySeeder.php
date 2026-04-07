<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'parent_id'   => null,
                'name'        => 'Electronics',
                'slug'        => 'electronics',
                'description' => 'Electronic devices and accessories',
                'is_active'   => 1,
                'sort_order'  => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'parent_id'   => null,
                'name'        => 'Fashion',
                'slug'        => 'fashion',
                'description' => 'Clothing, shoes and accessories',
                'is_active'   => 1,
                'sort_order'  => 2,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'parent_id'   => null,
                'name'        => 'Home & Living',
                'slug'        => 'home-living',
                'description' => 'Furniture and household items',
                'is_active'   => 1,
                'sort_order'  => 3,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'parent_id'   => null,
                'name'        => 'Sports & Outdoors',
                'slug'        => 'sports-outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'is_active'   => 1,
                'sort_order'  => 4,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($categories as $cat) {
            if (! $this->db->table('categories')->where('slug', $cat['slug'])->countAllResults()) {
                $this->db->table('categories')->insert($cat);
            }
        }
    }
}

