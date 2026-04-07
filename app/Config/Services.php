<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Override the default IncomingRequest with our custom subclass that
     * formally declares the jwtPayload property set by AuthFilter.
     */
    public static function request(
        ?\Config\App $config = null,
        bool $getShared = true
    ): \App\HTTP\IncomingRequest {
        if ($getShared) {
            return static::getSharedInstance('request', $config);
        }

        $config ??= config('App');

        return new \App\HTTP\IncomingRequest(
            $config,
            static::uri(),
            'php://input',
            new \CodeIgniter\HTTP\UserAgent()
        );
    }
}
