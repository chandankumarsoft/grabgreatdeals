<?php

namespace App\Controllers\Api\V1\Admin;

use App\Controllers\Api\V1\BaseApiController;
use App\Services\UserService;

class UserController extends BaseApiController
{
    protected UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
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
        $params = [
            'page'     => max(1, (int) ($this->request->getGet('page')     ?? 1)),
            'per_page' => min(100, max(1, (int) ($this->request->getGet('per_page') ?? 15))),
            'role'     => $this->request->getGet('role')   ?? '',
            'search'   => $this->request->getGet('search') ?? '',
            'status'   => $this->request->getGet('status'),
        ];

        return $this->respondSuccess('Users retrieved', $this->userService->list($params));
    }

    /**
     * GET /admin/users/{id}
     * Retrieve a single user by ID.
     */
    public function show(int $id)
    {
        $user = $this->userService->getById($id);

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
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        if (! $this->userService->getById($id)) {
            return $this->respondNotFound('User not found');
        }

        if ((int) $id === $this->getAuthUserId()) {
            return $this->respondError('You cannot change your own status', [], 422);
        }

        $updated = $this->userService->updateStatus($id, (int) $this->request->getJSON()->is_active);

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
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        if (! $this->userService->getById($id)) {
            return $this->respondNotFound('User not found');
        }

        if ((int) $id === $this->getAuthUserId()) {
            return $this->respondError('You cannot change your own role', [], 422);
        }

        $updated = $this->userService->updateRole($id, $this->request->getJSON()->role);

        return $this->respondSuccess('User role updated', $updated);
    }
}
