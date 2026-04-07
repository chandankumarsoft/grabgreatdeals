<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name'       => 'Admin',
                'email'      => 'admin@grabgreatdeals.com',
                'password'   => password_hash('Admin@1234', PASSWORD_BCRYPT),
                'phone'      => '+60123456789',
                'role'       => 'admin',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name'       => 'Test Customer',
                'email'      => 'customer@grabgreatdeals.com',
                'password'   => password_hash('Customer@1234', PASSWORD_BCRYPT),
                'phone'      => '+60198765432',
                'role'       => 'customer',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($data as $row) {
            if (! $this->db->table('users')->where('email', $row['email'])->countAllResults()) {
                $this->db->table('users')->insert($row);
            }
        }
    }
}

