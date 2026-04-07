<?php

namespace App\Controllers\Api\V1;

use App\Services\AuthService;

class AuthController extends BaseApiController
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register()
    {
        if (! $this->validate('auth_register')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $result = $this->authService->register($this->request->getJSON(true));

        if (! $result) {
            return $this->respondError('Registration failed', $this->authService->getValidationErrors(), 422);
        }

        return $this->respondCreated('Registration successful', $result);
    }

    public function login()
    {
        if (! $this->validate('auth_login')) {
            return $this->respondValidationErrors($this->validator->getErrors());
        }

        $body  = $this->request->getJSON(true);
        $result = $this->authService->login($body['email'], $body['password']);

        if ($result === 'inactive') {
            return $this->respondError('Your account has been deactivated', [], 403);
        }

        if (! $result) {
            return $this->respondError('Invalid email or password', [], 401);
        }

        return $this->respondSuccess('Login successful', $result);
    }

    public function logout()
    {
        // JWT is stateless — logout is handled client-side by discarding the token.
        // For token revocation in production, implement a token blacklist (Redis / DB table).
        return $this->respondSuccess('Logged out successfully');
    }
}
