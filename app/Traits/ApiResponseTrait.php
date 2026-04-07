<?php

namespace App\Traits;

trait ApiResponseTrait
{
    protected function respond(bool $status, string $message, $data = null, array $errors = [], int $statusCode = 200)
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

    // ─── 2xx Success ────────────────────────────────────────────────────────

    protected function respondSuccess(string $message, $data = null, int $statusCode = 200)
    {
        return $this->respond(true, $message, $data, [], $statusCode);
    }

    protected function respondCreated(string $message, $data = null)
    {
        return $this->respond(true, $message, $data, [], 201);
    }

    protected function respondNoContent(string $message = 'No content')
    {
        return $this->respond(true, $message, null, [], 204);
    }

    // ─── 4xx / 5xx Errors ───────────────────────────────────────────────────

    protected function respondError(string $message, array $errors = [], int $statusCode = 400)
    {
        return $this->respond(false, $message, null, $errors, $statusCode);
    }

    protected function respondValidationErrors(array $errors)
    {
        return $this->respondError('Validation failed', $errors, 422);
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

    protected function respondServerError(string $message = 'Internal server error')
    {
        return $this->respondError($message, [], 500);
    }
}
