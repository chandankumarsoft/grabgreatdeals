<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Jwt as JwtConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || ! str_starts_with($authHeader, 'Bearer ')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Missing or invalid Authorization header',
                    'data'    => null,
                    'errors'  => [],
                ]);
        }

        $token = substr($authHeader, 7);
        $jwtConfig = config(JwtConfig::class);

        try {
            $payload = JWT::decode($token, new Key($jwtConfig->secretKey, 'HS256'));
            $request->jwtPayload = $payload;
        } catch (Throwable $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Invalid or expired token',
                    'data'    => null,
                    'errors'  => [],
                ]);
        }

        if (! empty($arguments) && ! in_array($payload->role, $arguments)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Forbidden. Insufficient permissions.',
                    'data'    => null,
                    'errors'  => [],
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
