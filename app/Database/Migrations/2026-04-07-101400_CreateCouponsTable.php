<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCouponsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            // 'percent' discounts by %, 'fixed' deducts flat amount
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['percent', 'fixed'],
                'default'    => 'percent',
            ],
            'value' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            // minimum cart total required to use coupon (0 = no minimum)
            'min_order_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            // max discount cap for percent coupons (null = no cap)
            'max_discount_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            // how many times the coupon can be used across all users (null = unlimited)
            'usage_limit' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            // how many times this coupon has been used
            'used_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            // per-user usage limit (null = unlimited)
            'usage_limit_per_user' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'is_active' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'starts_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('coupons');

        // coupon_usages — tracks per-user usage for limits
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'coupon_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'order_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'discount_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('coupon_id', 'coupons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('coupon_usages');
    }

    public function down(): void
    {
        $this->forge->dropTable('coupon_usages', true);
        $this->forge->dropTable('coupons', true);
    }
}
