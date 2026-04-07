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
        $rules = [
            'name'  => 'permit_empty|min_length[2]|max_length[100]',
            'phone' => 'permit_empty|max_length[20]',
        ];

        if (! $this->validate($rules)) {
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
        $rules = [
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

        if (! $this->validate($rules)) {
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
