<?php

namespace App\Controllers\Api\V1;

use App\Services\UserService;

class UserController extends BaseApiController
{
    protected UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function profile()
    {
        $userId  = (int) $this->getAuthUserId();
        $profile = $this->userService->getProfile($userId);

        if (! $profile) {
            return $this->respondNotFound('User not found');
        }

        return $this->respondSuccess('Profile retrieved', $profile);
    }

    public function updateProfile()
    {
        if (! $this->validate('user_update_profile')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $userId = (int) $this->getAuthUserId();
        $result = $this->userService->updateProfile($userId, $this->request->getJSON(true));

        if ($result === false) {
            return $this->respondError('No updatable fields provided');
        }

        return $this->respondSuccess('Profile updated', $result);
    }

    public function addresses()
    {
        $userId    = (int) $this->getAuthUserId();
        $addresses = $this->userService->getAddresses($userId);

        return $this->respondSuccess('Addresses retrieved', $addresses);
    }

    public function addAddress()
    {
        if (! $this->validate('user_add_address')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $userId = (int) $this->getAuthUserId();
        $result = $this->userService->addAddress($userId, $this->request->getJSON(true));

        if (! $result) {
            return $this->respondError('Failed to add address');
        }

        return $this->respondCreated('Address added', $result);
    }

    public function deleteAddress(int $addressId)
    {
        $userId  = (int) $this->getAuthUserId();
        $deleted = $this->userService->deleteAddress($userId, $addressId);

        if (! $deleted) {
            return $this->respondNotFound('Address not found');
        }

        return $this->respondSuccess('Address deleted');
    }
}
