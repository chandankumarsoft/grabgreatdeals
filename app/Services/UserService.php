<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\UserAddressModel;

class UserService
{
    private const USER_FIELDS = 'id, name, email, phone, role, is_active, created_at, updated_at';

    protected UserModel        $userModel;
    protected UserAddressModel $addressModel;

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->addressModel = new UserAddressModel();
    }

    // ─── Admin: User Management ─────────────────────────────────────────────

    public function list(array $params = []): array
    {
        $page    = (int) ($params['page']     ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $role    = (string) ($params['role']   ?? '');
        $search  = (string) ($params['search'] ?? '');
        $status  = $params['status'] ?? null;

        $builder = $this->userModel->select(self::USER_FIELDS);

        if ($role !== '') {
            $builder->where('role', $role);
        }

        if ($search !== '') {
            $builder->groupStart()
                    ->like('name', $search)
                    ->orLike('email', $search)
                    ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder->where('is_active', (int) $status);
        }

        $total = $builder->countAllResults(false);
        $items = $builder->orderBy('created_at', 'DESC')
                         ->findAll($perPage, ($page - 1) * $perPage);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    public function getById(int $id): ?array
    {
        return $this->userModel
            ->select(self::USER_FIELDS)
            ->find($id);
    }

    public function updateStatus(int $id, int $isActive): ?array
    {
        $this->userModel->update($id, ['is_active' => $isActive]);

        return $this->getById($id);
    }

    public function updateRole(int $id, string $role): ?array
    {
        $this->userModel->update($id, ['role' => $role]);

        return $this->getById($id);
    }

    // ─── Customer: Profile & Addresses ─────────────────────────────────────

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
            'name'  => isset($data['name'])  ? trim($data['name'])  : null,
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
        ], fn($v) => $v !== null && $v !== '');

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
