<?php

namespace App\Services;

use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\ProductImageModel;
use App\Models\CategoryModel;
use App\Models\ReviewModel;

class ProductService
{
    protected ProductModel        $productModel;
    protected ProductVariantModel $variantModel;
    protected ProductImageModel   $imageModel;
    protected CategoryModel       $categoryModel;
    protected ReviewModel         $reviewModel;

    public function __construct()
    {
        $this->productModel  = new ProductModel();
        $this->variantModel  = new ProductVariantModel();
        $this->imageModel    = new ProductImageModel();
        $this->categoryModel = new CategoryModel();
        $this->reviewModel   = new ReviewModel();
    }

    public function getProducts(array $params = []): array
    {
        return $this->productModel->getProductList($params);
    }

    public function getAdminProducts(array $params = []): array
    {
        return $this->productModel->getAdminList($params);
    }

    public function getProductBySlug(string $slug): ?array
    {
        $product = $this->productModel->findBySlug($slug);

        if (! $product) {
            return null;
        }

        $product['variants'] = $this->variantModel->getByProduct((int) $product['id']);
        $product['images']   = $this->imageModel->getByProduct((int) $product['id']);
        $product             = array_merge($product, $this->reviewModel->getRatingStats((int) $product['id']));

        return $product;
    }

    public function getProductById(int $id): ?array
    {
        $product = $this->productModel->find($id);

        if (! $product) {
            return null;
        }

        $product['variants'] = $this->variantModel->getByProduct($id);
        $product['images']   = $this->imageModel->getByProduct($id);
        $product             = array_merge($product, $this->reviewModel->getRatingStats($id));

        return $product;
    }

    public function create(array $data): array|false
    {
        $slug = $this->generateUniqueSlug($data['name']);

        $productId = $this->productModel->insert([
            'category_id' => $data['category_id'] ?? null,
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'sale_price'  => $data['sale_price']  ?? null,
            'stock'       => $data['stock']       ?? 0,
            'sku'         => $data['sku']         ?? null,
            'thumbnail'   => $data['thumbnail']   ?? null,
            'is_active'   => isset($data['is_active'])   ? (int) $data['is_active']   : 1,
            'is_featured' => isset($data['is_featured']) ? (int) $data['is_featured'] : 0,
        ]);

        if (! $productId) {
            return false;
        }

        if (! empty($data['variants']) && is_array($data['variants'])) {
            $this->syncVariants((int) $productId, $data['variants']);
        }

        return $this->getProductById((int) $productId);
    }

    public function update(int $id, array $data): array|false
    {
        $product = $this->productModel->find($id);

        if (! $product) {
            return false;
        }

        $update = array_filter([
            'category_id' => $data['category_id'] ?? null,
            'name'        => $data['name']        ?? null,
            'description' => $data['description'] ?? null,
            'price'       => isset($data['price'])       ? (float) $data['price']       : null,
            'sale_price'  => array_key_exists('sale_price', $data) ? ($data['sale_price'] !== '' ? (float) $data['sale_price'] : null) : null,
            'stock'       => isset($data['stock'])       ? (int) $data['stock']          : null,
            'sku'         => $data['sku']         ?? null,
            'thumbnail'   => $data['thumbnail']   ?? null,
            'is_active'   => isset($data['is_active'])   ? (int) $data['is_active']   : null,
            'is_featured' => isset($data['is_featured']) ? (int) $data['is_featured'] : null,
        ], fn($v) => $v !== null);

        if (isset($data['name']) && $data['name'] !== $product['name']) {
            $update['slug'] = $this->generateUniqueSlug($data['name'], $id);
        }

        if (! empty($update)) {
            $this->productModel->update($id, $update);
        }

        if (isset($data['variants']) && is_array($data['variants'])) {
            $this->syncVariants($id, $data['variants']);
        }

        return $this->getProductById($id);
    }

    public function delete(int $id): bool
    {
        $product = $this->productModel->find($id);

        if (! $product) {
            return false;
        }

        return (bool) $this->productModel->delete($id);
    }

    public function uploadImages(int $productId, array $files, bool $isPrimary = false): array
    {
        $product = $this->productModel->find($productId);

        if (! $product) {
            return [];
        }

        $uploadPath = WRITEPATH . 'uploads/products/';

        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $inserted  = [];
        $sortStart = count($this->imageModel->getByProduct($productId));

        foreach ($files as $index => $file) {
            if (! $file->isValid() || $file->hasMoved()) {
                continue;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

            if (! in_array($file->getMimeType(), $allowed)) {
                continue;
            }

            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);

            $setAsPrimary = $isPrimary && $index === 0;

            if ($setAsPrimary) {
                $this->imageModel->clearPrimary($productId);
            }

            $imageId = $this->imageModel->insert([
                'product_id' => $productId,
                'image_path' => 'uploads/products/' . $newName,
                'alt_text'   => $product['name'],
                'sort_order' => $sortStart + $index,
                'is_primary' => $setAsPrimary ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($imageId) {
                $inserted[] = $this->imageModel->find($imageId);
            }
        }

        return $inserted;
    }

    public function deleteImage(int $productId, int $imageId): bool
    {
        $image = $this->imageModel->getByProductAndId($productId, $imageId);

        if (! $image) {
            return false;
        }

        $fullPath = WRITEPATH . $image['image_path'];

        if (is_file($fullPath)) {
            unlink($fullPath);
        }

        return (bool) $this->imageModel->delete($imageId);
    }

    public function getValidationErrors(): array
    {
        return $this->productModel->errors();
    }

    private function syncVariants(int $productId, array $variants): void
    {
        $this->variantModel->deleteByProduct($productId);

        foreach ($variants as $variant) {
            if (empty($variant['name']) || empty($variant['value'])) {
                continue;
            }

            $this->variantModel->insert([
                'product_id'       => $productId,
                'name'             => $variant['name'],
                'value'            => $variant['value'],
                'price_adjustment' => $variant['price_adjustment'] ?? 0.00,
                'stock'            => $variant['stock']            ?? 0,
                'sku'              => $variant['sku']              ?? null,
                'is_active'        => 1,
            ]);
        }
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = strtolower(trim(preg_replace('/[\s\W]+/', '-', $name), '-'));
        $base = $slug;
        $i    = 1;

        while ($this->productModel->slugExists($slug, $excludeId)) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
