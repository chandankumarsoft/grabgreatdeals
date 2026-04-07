<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    // ─── Auth ────────────────────────────────────────────────────────────────

    public array $auth_register = [
        'name'     => 'required|min_length[2]|max_length[100]',
        'email'    => 'required|valid_email|max_length[150]|is_unique[users.email]',
        'password' => 'required|min_length[8]|max_length[72]',
        'phone'    => 'permit_empty|max_length[20]',
    ];

    public array $auth_login = [
        'email'    => 'required|valid_email',
        'password' => 'required',
    ];

    // ─── User / Address ──────────────────────────────────────────────────────

    public array $user_update_profile = [
        'name'  => 'permit_empty|min_length[2]|max_length[100]',
        'phone' => 'permit_empty|max_length[20]',
    ];

    public array $user_add_address = [
        'label'          => 'permit_empty|max_length[50]',
        'recipient_name' => 'required|max_length[100]',
        'phone'          => 'required|max_length[20]',
        'address_line1'  => 'required|max_length[255]',
        'address_line2'  => 'permit_empty|max_length[255]',
        'city'           => 'required|max_length[100]',
        'state'          => 'required|max_length[100]',
        'postal_code'    => 'required|max_length[20]',
        'country'        => 'permit_empty|max_length[100]',
        'is_default'     => 'permit_empty|in_list[0,1]',
    ];

    // ─── Cart ────────────────────────────────────────────────────────────────

    public array $cart_add_item = [
        'product_id' => 'required|integer|greater_than[0]',
        'variant_id' => 'permit_empty|integer|greater_than[0]',
        'quantity'   => 'permit_empty|integer|greater_than[0]',
    ];

    public array $cart_update_item = [
        'quantity' => 'required|integer|greater_than[0]',
    ];

    // ─── Order ───────────────────────────────────────────────────────────────

    public array $order_checkout = [
        'shipping_name'    => 'required|max_length[100]',
        'shipping_phone'   => 'required|max_length[20]',
        'shipping_address' => 'required|max_length[500]',
        'payment_method'   => 'permit_empty|in_list[cod,card,fpx,ewallet,bank_transfer]',
        'coupon_code'      => 'permit_empty|max_length[50]',
        'notes'            => 'permit_empty|max_length[500]',
    ];

    // ─── Review ──────────────────────────────────────────────────────────────

    public array $review_create = [
        'rating' => 'required|integer|greater_than[0]|less_than[6]',
        'title'  => 'permit_empty|max_length[150]',
        'body'   => 'permit_empty|max_length[2000]',
    ];

    public array $review_update = [
        'rating' => 'permit_empty|integer|greater_than[0]|less_than[6]',
        'title'  => 'permit_empty|max_length[150]',
        'body'   => 'permit_empty|max_length[2000]',
    ];

    // ─── Coupon ──────────────────────────────────────────────────────────────

    public array $coupon_apply = [
        'code' => 'required|max_length[50]',
    ];

    // ─── Admin: Product ──────────────────────────────────────────────────────

    public array $admin_product_create = [
        'name'        => 'required|min_length[2]|max_length[200]',
        'category_id' => 'permit_empty|integer',
        'price'       => 'required|decimal|greater_than[0]',
        'sale_price'  => 'permit_empty|decimal|greater_than[0]',
        'stock'       => 'permit_empty|integer|greater_than_equal_to[0]',
        'sku'         => 'permit_empty|max_length[100]|is_unique[products.sku]',
        'is_active'   => 'permit_empty|in_list[0,1]',
        'is_featured' => 'permit_empty|in_list[0,1]',
    ];

    /**
     * Base rules for product update. The 'sku' field intentionally omits
     * is_unique here; the controller appends the id-exclusion constraint
     * at runtime: is_unique[products.sku,id,{$id}]
     */
    public array $admin_product_update = [
        'name'        => 'permit_empty|min_length[2]|max_length[200]',
        'category_id' => 'permit_empty|integer',
        'price'       => 'permit_empty|decimal|greater_than[0]',
        'sale_price'  => 'permit_empty|decimal|greater_than[0]',
        'stock'       => 'permit_empty|integer|greater_than_equal_to[0]',
        'sku'         => 'permit_empty|max_length[100]',
        'is_active'   => 'permit_empty|in_list[0,1]',
        'is_featured' => 'permit_empty|in_list[0,1]',
    ];

    // ─── Admin: Order ────────────────────────────────────────────────────────

    public array $admin_order_update_status = [
        'status' => 'required|in_list[pending,confirmed,processing,shipped,delivered,cancelled,refunded]',
    ];

    // ─── Admin: User ─────────────────────────────────────────────────────────

    public array $admin_user_update_status = [
        'is_active' => 'required|in_list[0,1]',
    ];

    public array $admin_user_update_role = [
        'role' => 'required|in_list[customer,admin]',
    ];

    // ─── Admin: Review ───────────────────────────────────────────────────────

    public array $admin_review_approve = [
        'is_approved' => 'required|in_list[0,1]',
    ];

    // ─── Admin: Coupon ───────────────────────────────────────────────────────

    public array $admin_coupon_create = [
        'code'                 => 'required|max_length[50]',
        'type'                 => 'required|in_list[percent,fixed]',
        'value'                => 'required|decimal|greater_than[0]',
        'description'          => 'permit_empty|max_length[255]',
        'min_order_amount'     => 'permit_empty|decimal|greater_than_equal_to[0]',
        'max_discount_amount'  => 'permit_empty|decimal|greater_than[0]',
        'usage_limit'          => 'permit_empty|integer|greater_than[0]',
        'usage_limit_per_user' => 'permit_empty|integer|greater_than[0]',
        'is_active'            => 'permit_empty|in_list[0,1]',
        'starts_at'            => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'expires_at'           => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    public array $admin_coupon_update = [
        'code'                 => 'permit_empty|max_length[50]',
        'type'                 => 'permit_empty|in_list[percent,fixed]',
        'value'                => 'permit_empty|decimal|greater_than[0]',
        'description'          => 'permit_empty|max_length[255]',
        'min_order_amount'     => 'permit_empty|decimal|greater_than_equal_to[0]',
        'max_discount_amount'  => 'permit_empty|decimal|greater_than[0]',
        'usage_limit'          => 'permit_empty|integer|greater_than[0]',
        'usage_limit_per_user' => 'permit_empty|integer|greater_than[0]',
        'is_active'            => 'permit_empty|in_list[0,1]',
        'starts_at'            => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'expires_at'           => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    // ─── Admin: Payment ──────────────────────────────────────────────────────

    public array $admin_payment_update = [
        'status'           => 'required|in_list[paid,failed,refunded]',
        'transaction_id'   => 'permit_empty|max_length[255]',
        'gateway_response' => 'permit_empty|max_length[2000]',
    ];
}
