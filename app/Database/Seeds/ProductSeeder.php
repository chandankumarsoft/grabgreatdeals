<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $electronicsId = $this->db->table('categories')->where('slug', 'electronics')->get()->getRow()?->id;
        $fashionId     = $this->db->table('categories')->where('slug', 'fashion')->get()->getRow()?->id;

        if (! $electronicsId || ! $fashionId) {
            return;
        }

        $products = [
            [
                'category_id' => $electronicsId,
                'name'        => 'Wireless Bluetooth Earbuds',
                'slug'        => 'wireless-bluetooth-earbuds',
                'description' => 'High quality wireless earbuds with noise cancellation.',
                'price'       => 129.90,
                'sale_price'  => 99.90,
                'stock'       => 150,
                'sku'         => 'ELEC-001',
                'is_active'   => 1,
                'is_featured' => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'category_id' => $electronicsId,
                'name'        => 'USB-C Fast Charger 65W',
                'slug'        => 'usb-c-fast-charger-65w',
                'description' => 'Universal 65W fast charger compatible with all USB-C devices.',
                'price'       => 79.90,
                'sale_price'  => null,
                'stock'       => 300,
                'sku'         => 'ELEC-002',
                'is_active'   => 1,
                'is_featured' => 0,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'category_id' => $fashionId,
                'name'        => 'Classic Polo T-Shirt',
                'slug'        => 'classic-polo-t-shirt',
                'description' => 'Premium cotton polo t-shirt, available in multiple colors.',
                'price'       => 49.90,
                'sale_price'  => 39.90,
                'stock'       => 200,
                'sku'         => 'FASH-001',
                'is_active'   => 1,
                'is_featured' => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($products as $product) {
            if (! $this->db->table('products')->where('sku', $product['sku'])->countAllResults()) {
                $this->db->table('products')->insert($product);
            }
        }
    }
}

