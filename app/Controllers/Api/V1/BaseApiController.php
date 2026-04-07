<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;

abstract class BaseApiController extends BaseController
{
    use ApiResponseTrait;

    /**
     * @var \App\HTTP\IncomingRequest $request
     */
    protected $request;

    protected function getValidatedInput(array $rules, array $messages = []): array|false
    {
        if (! $this->validate($rules, $messages)) {
            return false;
        }

        return $this->validator->getValidated();
    }

    protected function getAuthUserId(): ?int
    {
        if (empty($this->request->getHeaderLine('Authorization'))) {
            return null;
        }

        return isset($this->request->jwtPayload->sub)
            ? (int) $this->request->jwtPayload->sub
            : null;
    }
}
