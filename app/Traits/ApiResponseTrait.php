<?php

namespace App\Traits;

trait ApiResponseTrait
{
    protected function respond(bool $status, string $message, $data = [], array $errors = [], int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status'  => $status,
                'message' => $message,
                'data'    => $data,
                'errors'  => $errors,
            ]);
    }

    protected function respondSuccess(string $message, $data = [], int $statusCode = 200)
    {
        return $this->respond(true, $message, $data, [], $statusCode);
    }

    protected function respondError(string $message, array $errors = [], int $statusCode = 400)
    {
        return $this->respond(false, $message, [], $errors, $statusCode);
    }

    protected function respondUnauthorized(string $message = 'Unauthorized')
    {
        return $this->respondError($message, [], 401);
    }

    protected function respondForbidden(string $message = 'Forbidden')
    {
        return $this->respondError($message, [], 403);
    }

    protected function respondNotFound(string $message = 'Resource not found')
    {
        return $this->respondError($message, [], 404);
    }

    protected function respondValidationError(array $errors)
    {
        return $this->respondError('Validation failed', $errors, 422);
    }
}
