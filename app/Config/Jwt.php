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

        $this->secretKey  = env('jwt.secretKey', 'change-this-secret');
        $this->expiration = (int) env('jwt.expiration', 3600);
    }
}
