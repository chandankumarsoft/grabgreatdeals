<?php

namespace App\Models;

use CodeIgniter\Model;

class UserAddressModel extends Model
{
    protected $table         = 'user_addresses';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)->orderBy('is_default', 'DESC')->findAll();
    }

    public function getByUserAndId(int $userId, int $id): ?array
    {
        return $this->where('user_id', $userId)->where('id', $id)->first();
    }

    public function clearDefault(int $userId): void
    {
        $this->where('user_id', $userId)->set('is_default', 0)->update();
    }
}
