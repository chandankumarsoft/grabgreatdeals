<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\UserAddressModel;

class UserService
{
    protected UserModel        $userModel;
    protected UserAddressModel $addressModel;

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->addressModel = new UserAddressModel();
    }

    public function getProfile(int $userId): ?array
    {
        $user = $this->userModel->find($userId);

        if (! $user) {
            return null;
        }

        unset($user['password']);

        return $user;
    }

    public function updateProfile(int $userId, array $data): array|false
    {
        $allowed = array_filter([
            'name'  => $data['name']  ?? null,
            'phone' => $data['phone'] ?? null,
        ], fn($v) => $v !== null);

        if (empty($allowed)) {
            return false;
        }

        $this->userModel->update($userId, $allowed);

        return $this->getProfile($userId);
    }

    public function getValidationErrors(): array
    {
        return $this->userModel->errors();
    }

    public function getAddresses(int $userId): array
    {
        return $this->addressModel->getByUser($userId);
    }

    public function addAddress(int $userId, array $data): array|false
    {
        if (! empty($data['is_default'])) {
            $this->addressModel->clearDefault($userId);
        }

        $insertId = $this->addressModel->insert([
            'user_id'        => $userId,
            'label'          => $data['label']          ?? 'home',
            'recipient_name' => $data['recipient_name'],
            'phone'          => $data['phone'],
            'address_line1'  => $data['address_line1'],
            'address_line2'  => $data['address_line2']  ?? null,
            'city'           => $data['city'],
            'state'          => $data['state'],
            'postal_code'    => $data['postal_code'],
            'country'        => $data['country']        ?? 'MY',
            'is_default'     => ! empty($data['is_default']) ? 1 : 0,
        ]);

        if (! $insertId) {
            return false;
        }

        return $this->addressModel->find($insertId);
    }

    public function deleteAddress(int $userId, int $addressId): bool
    {
        $address = $this->addressModel->getByUserAndId($userId, $addressId);

        if (! $address) {
            return false;
        }

        return (bool) $this->addressModel->delete($addressId);
    }
}
