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

    protected function getPaginationParams(int $default = 15, int $max = 100): array
    {
        return [
            'page'     => max(1, (int) ($this->request->getGet('page')     ?? 1)),
            'per_page' => max(1, min($max, (int) ($this->request->getGet('per_page') ?? $default))),
        ];
    }
}
