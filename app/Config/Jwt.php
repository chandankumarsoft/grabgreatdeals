<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Jwt extends BaseConfig
{
    public string $secretKey  = '';
    public int    $expiration = 3600;

    public function __construct()
    {
        parent::__construct();

        $this->secretKey  = env('jwt.secretKey', '');
        $this->expiration = (int) env('jwt.expiration', 3600);

        // Hard-fail if no real key is configured.
        // This prevents silent deployments with an empty/insecure secret.
        if (ENVIRONMENT === 'production' && strlen($this->secretKey) < 32) {
            throw new \RuntimeException(
                'jwt.secretKey must be set to a value of at least 32 characters in production.'
            );
        }

        // In non-production environments use a safe fallback so the app still boots.
        if ($this->secretKey === '') {
            $this->secretKey = 'dev-only-insecure-key-change-in-production';
        }
    }
}
