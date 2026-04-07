<?php

namespace App\Services;

use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\PaymentModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\UserModel;
use App\Services\CouponService;
use App\Services\EmailService;

class OrderService
{
    protected OrderModel          $orderModel;
    protected OrderItemModel      $orderItemModel;
    protected PaymentModel        $paymentModel;
    protected ProductModel        $productModel;
    protected ProductVariantModel $variantModel;
    protected CartModel           $cartModel;
    protected CartItemModel       $cartItemModel;
    protected CouponService       $couponService;
    protected UserModel           $userModel;
    protected EmailService        $emailService;

    public function __construct()
    {
        $this->orderModel     = new OrderModel();
        $this->orderItemModel = new OrderItemModel();
        $this->paymentModel   = new PaymentModel();
        $this->productModel   = new ProductModel();
        $this->variantModel   = new ProductVariantModel();
        $this->cartModel      = new CartModel();
        $this->cartItemModel  = new CartItemModel();
        $this->couponService  = new CouponService();
        $this->userModel      = new UserModel();
        $this->emailService   = new EmailService();
    }

    public function checkout(int $userId, array $data): array|string
    {
        $cart = $this->cartModel->getOrCreateForUser($userId);
        $items = $this->cartItemModel->getByCart((int) $cart['id']);

        if (empty($items)) {
            return 'empty_cart';
        }

        $stockErrors = $this->validateStock($items);

        if (! empty($stockErrors)) {
            return $stockErrors;
        }

        $subtotal    = 0.0;
        $shippingFee = (float) ($data['shipping_fee'] ?? 0.00);

        foreach ($items as $item) {
            $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
        }

        // Coupon validation
        $discountAmount = 0.0;
        $coupon         = null;

        if (! empty($data['coupon_code'])) {
            $couponResult = $this->couponService->validate($data['coupon_code'], $userId, $subtotal);

            if (is_string($couponResult)) {
                return $couponResult;
            }

            $coupon         = $couponResult;
            $discountAmount = $this->couponService->calculateDiscount($coupon, $subtotal);
        }

        $total = max(0, $subtotal + $shippingFee - $discountAmount);

        $db = db_connect();
        $db->transStart();

        $orderNumber = $this->orderModel->generateOrderNumber();

        $orderId = $this->orderModel->insert([
            'user_id'          => $userId,
            'order_number'     => $orderNumber,
            'status'           => 'pending',
            'subtotal'         => $subtotal,
            'shipping_fee'     => $shippingFee,
            'discount_amount'  => $discountAmount,
            'total'            => $total,
            'shipping_name'    => $data['shipping_name'],
            'shipping_phone'   => $data['shipping_phone'],
            'shipping_address' => $data['shipping_address'],
            'notes'            => $data['notes'] ?? null,
        ]);

        foreach ($items as $item) {
            $variantLabel = null;

            if ($item['variant_name'] && $item['variant_value']) {
                $variantLabel = $item['variant_name'] . ': ' . $item['variant_value'];
            }

            $lineSubtotal = (float) $item['unit_price'] * (int) $item['quantity'];

            $this->orderItemModel->insert([
                'order_id'     => $orderId,
                'product_id'   => $item['product_id'],
                'variant_id'   => $item['variant_id'],
                'product_name' => $item['product_name'],
                'variant_label' => $variantLabel,
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'subtotal'     => $lineSubtotal,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            $this->decrementStock($item);
        }

        $paymentMethod = $data['payment_method'] ?? 'cod';

        $this->paymentModel->insert([
            'order_id' => $orderId,
            'method'   => $paymentMethod,
            'status'   => $paymentMethod === 'cod' ? 'pending' : 'pending',
            'amount'   => $total,
        ]);

        $this->cartItemModel->clearCart((int) $cart['id']);

        if ($coupon !== null) {
            $this->couponService->recordUsage((int) $coupon['id'], $userId, (int) $orderId, $discountAmount);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return 'transaction_failed';
        }

        $result = $this->getOrderById($userId, (int) $orderId);

        // Send confirmation email — failure is logged, never breaks the order response
        $user = $this->userModel->find($userId);
        if ($user && $result) {
            $this->emailService->sendOrderConfirmation($result, (string) $user['email'], (string) $user['name']);
        }

        return $result;
    }

    public function getUserOrders(int $userId, int $perPage = 15, int $page = 1): array
    {
        $result = $this->orderModel->getByUser($userId, $perPage, $page);

        if (! empty($result['items'])) {
            $ids = array_column($result['items'], 'id');

            $allItems    = $this->orderItemModel->getByOrders($ids);
            $allPayments = $this->paymentModel->getByOrders($ids);

            $itemsByOrder = [];
            foreach ($allItems as $item) {
                $itemsByOrder[(int) $item['order_id']][] = $item;
            }

            $paymentByOrder = [];
            foreach ($allPayments as $payment) {
                $paymentByOrder[(int) $payment['order_id']] = $payment;
            }

            foreach ($result['items'] as &$order) {
                $oid = (int) $order['id'];
                $order['items']   = $itemsByOrder[$oid] ?? [];
                $order['payment'] = $paymentByOrder[$oid] ?? null;
            }
            unset($order);
        }

        return $result;
    }

    public function getOrderById(int $userId, int $orderId, bool $isAdmin = false): ?array
    {
        if ($isAdmin) {
            $order = $this->orderModel->find($orderId);
        } else {
            $order = $this->orderModel->where('user_id', $userId)->where('id', $orderId)->first();
        }

        if (! $order) {
            return null;
        }

        $order['items']   = $this->orderItemModel->getByOrder($orderId);
        $order['payment'] = $this->paymentModel->getByOrder($orderId);

        return $order;
    }

    public function getAdminOrders(int $perPage = 15, int $page = 1, string $status = ''): array
    {
        return $this->orderModel->getAdminList($perPage, $page, $status);
    }

    public function updateStatus(int $orderId, string $status): array|false
    {
        $order = $this->orderModel->find($orderId);

        if (! $order) {
            return false;
        }

        $this->orderModel->update($orderId, ['status' => $status]);

        if ($status === 'delivered') {
            $this->paymentModel->where('order_id', $orderId)->set(['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')])->update();
        }

        $result = $this->getOrderById(0, $orderId, true);

        // Notify customer of status change
        if ($result) {
            $user = $this->userModel->find($result['user_id']);
            if ($user) {
                $this->emailService->sendOrderStatusUpdate($result, (string) $user['email'], (string) $user['name']);
            }
        }

        return $result;
    }

    private function validateStock(array $items): array
    {
        $errors = [];

        foreach ($items as $item) {
            $stock = $item['variant_id']
                ? (int) ($item['variant_stock'] ?? 0)
                : (int) $item['product_stock'];

            if ((int) $item['quantity'] > $stock) {
                $errors[] = "Insufficient stock for: {$item['product_name']}";
            }
        }

        return $errors;
    }

    private function decrementStock(array $item): void
    {
        if ($item['variant_id']) {
            $variant = $this->variantModel->find($item['variant_id']);

            if ($variant) {
                $newStock = max(0, (int) $variant['stock'] - (int) $item['quantity']);
                $this->variantModel->update($item['variant_id'], ['stock' => $newStock]);
            }
        } else {
            $product  = $this->productModel->find($item['product_id']);
            $newStock = max(0, (int) $product['stock'] - (int) $item['quantity']);
            $this->productModel->update($item['product_id'], ['stock' => $newStock]);
        }
    }
}
