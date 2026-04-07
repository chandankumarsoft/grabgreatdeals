<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;

abstract class BaseApiController extends BaseController
{
    use ApiResponseTrait;

    protected function getValidatedInput(array $rules, array $messages = []): array|false
    {
        if (! $this->validate($rules, $messages)) {
            return false;
        }

        return $this->validator->getValidated();
    }

    protected function getAuthUserId(): ?int
    {
        $token = $this->request->getHeaderLine('Authorization');

        if (empty($token)) {
            return null;
        }

        return $this->request->jwtPayload->sub ?? null;
    }
}
