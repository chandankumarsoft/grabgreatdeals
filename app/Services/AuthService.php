<?php

namespace App\Services;

use App\Models\UserModel;
use Config\Jwt as JwtConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    protected UserModel $userModel;
    protected JwtConfig $jwtConfig;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwtConfig = config(JwtConfig::class);
    }

    public function register(array $data): array|false
    {
        $this->userModel->setValidationRule('password', 'required|min_length[8]|max_length[72]');

        $userId = $this->userModel->insert([
            'name'      => trim($data['name']),
            'email'     => strtolower(trim($data['email'])),
            'password'  => $data['password'],
            'phone'     => isset($data['phone']) ? trim($data['phone']) : null,
            'role'      => 'customer',
            'is_active' => 1,
        ]);

        if (! $userId) {
            return false;
        }

        $user = $this->userModel->find($userId);

        return [
            'user'  => $this->sanitizeUser($user),
            'token' => $this->generateToken($user),
        ];
    }

    public function login(string $email, string $password): array|false|string
    {
        $user = $this->userModel->findByEmail(strtolower(trim($email)));

        if (! $user || ! password_verify($password, $user['password'])) {
            return false;
        }

        if (! $this->userModel->isActive($user)) {
            return 'inactive';
        }

        return [
            'user'  => $this->sanitizeUser($user),
            'token' => $this->generateToken($user),
        ];
    }

    public function getValidationErrors(): array
    {
        return $this->userModel->errors();
    }

    private function generateToken(array $user): string
    {
        $now = time();

        $payload = [
            'iss' => base_url(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->jwtConfig->expiration,
            'sub' => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];

        return JWT::encode($payload, $this->jwtConfig->secretKey, 'HS256');
    }

    private function sanitizeUser(array $user): array
    {
        unset($user['password']);

        return $user;
    }
}
