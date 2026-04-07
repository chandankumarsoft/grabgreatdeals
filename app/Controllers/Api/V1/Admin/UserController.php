<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;
use App\Models\UserModel;

class UserController extends BaseApiController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * GET /admin/users
     * Paginated list of all users with optional filters.
     *
     * Query params:
     *   page     int     default 1
     *   per_page int     default 15
     *   role     string  filter by role (customer|admin)
     *   search   string  search name or email
     *   status   int     1 = active, 0 = inactive
     */
    public function index()
    {
        $page    = max(1, (int) ($this->request->getGet('page')     ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 15)));
        $role    = $this->request->getGet('role')   ?? '';
        $search  = $this->request->getGet('search') ?? '';
        $status  = $this->request->getGet('status');

        $builder = $this->userModel->select('id, name, email, phone, role, is_active, created_at, updated_at');

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

        return $this->respondSuccess('Users retrieved', [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ]);
    }

    /**
     * GET /admin/users/{id}
     * Retrieve a single user by ID.
     */
    public function show(int $id)
    {
        $user = $this->userModel
            ->select('id, name, email, phone, role, is_active, created_at, updated_at')
            ->find($id);

        if (! $user) {
            return $this->respondNotFound('User not found');
        }

        return $this->respondSuccess('User retrieved', $user);
    }

    /**
     * PUT /admin/users/{id}/status
     * Toggle a user's active status.
     * Body: { "is_active": 0|1 }
     */
    public function updateStatus(int $id)
    {
        $rules = ['is_active' => 'required|in_list[0,1]'];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $user = $this->userModel->find($id);

        if (! $user) {
            return $this->respondNotFound('User not found');
        }

        // Prevent admins from deactivating themselves
        if ((int) $id === $this->getAuthUserId()) {
            return $this->respondError('You cannot change your own status', [], 422);
        }

        $this->userModel->update($id, ['is_active' => (int) $this->request->getJSON()->is_active]);

        $updated = $this->userModel
            ->select('id, name, email, phone, role, is_active, created_at, updated_at')
            ->find($id);

        return $this->respondSuccess('User status updated', $updated);
    }

    /**
     * PUT /admin/users/{id}/role
     * Change a user's role.
     * Body: { "role": "customer|admin" }
     */
    public function updateRole(int $id)
    {
        $rules = ['role' => 'required|in_list[customer,admin]'];

        if (! $this->validate($rules)) {
            return $this->respondValidationError($this->validator->getErrors());
        }

        $user = $this->userModel->find($id);

        if (! $user) {
            return $this->respondNotFound('User not found');
        }

        if ((int) $id === $this->getAuthUserId()) {
            return $this->respondError('You cannot change your own role', [], 422);
        }

        $this->userModel->update($id, ['role' => $this->request->getJSON()->role]);

        $updated = $this->userModel
            ->select('id, name, email, phone, role, is_active, created_at, updated_at')
            ->find($id);

        return $this->respondSuccess('User role updated', $updated);
    }
}
