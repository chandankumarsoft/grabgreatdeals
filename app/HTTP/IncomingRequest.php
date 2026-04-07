<?php

namespace App\HTTP;

/**
 * Custom IncomingRequest that formally declares the jwtPayload property.
 *
 * This:
 *  - Eliminates the PHP 8.2 deprecated-dynamic-property warning seen in logs
 *  - Removes the Intelephense "Undefined property" static-analysis error
 *  - Keeps AuthFilter's $request->jwtPayload = $payload assignment valid
 */
class IncomingRequest extends \CodeIgniter\HTTP\IncomingRequest
{
    /**
     * Decoded JWT payload, set by AuthFilter on authenticated routes.
     * Contains at minimum: sub (int), email (string), role (string), iat, exp.
     */
    public ?object $jwtPayload = null;
}
