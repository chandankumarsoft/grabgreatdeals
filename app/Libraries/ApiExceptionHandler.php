<?php

namespace App\Libraries;

use CodeIgniter\Debug\BaseExceptionHandler;
use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Global exception handler for API routes.
 *
 * - API requests  → always return the standard JSON envelope.
 * - Other routes  → delegate to CI4's built-in ExceptionHandler (HTML views).
 *
 * In production the exception message is hidden and a generic phrase is
 * returned instead, preventing stack-trace / path leakage.
 */
class ApiExceptionHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        // CLI — always use the default handler
        if ($request instanceof CLIRequest) {
            (new ExceptionHandler($this->config))->handle(
                $exception, $request, $response, $statusCode, $exitCode,
            );
            return;
        }

        // Non-API web request — use the default HTML handler
        if ($request instanceof IncomingRequest && ! $this->isApiRequest($request)) {
            (new ExceptionHandler($this->config))->handle(
                $exception, $request, $response, $statusCode, $exitCode,
            );
            return;
        }

        // ── API request: always respond with the standard JSON envelope ──────

        // In development expose the real message; in production hide it.
        $message = (ENVIRONMENT !== 'production')
            ? ($exception->getMessage() ?: $this->genericMessage($statusCode))
            : $this->genericMessage($statusCode);

        $response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'status'  => false,
                'message' => $message,
                'data'    => null,
                'errors'  => [],
            ]))
            ->send();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function isApiRequest(IncomingRequest $request): bool
    {
        return str_starts_with($request->getPath(), 'api/')
            || str_contains($request->getHeaderLine('Accept'), 'application/json');
    }

    private function genericMessage(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'An unexpected error occurred. Please try again later.',
            $statusCode === 404 => 'The requested resource was not found.',
            $statusCode === 403 => 'Forbidden.',
            $statusCode === 401 => 'Unauthorized.',
            $statusCode >= 400  => 'Bad request.',
            default             => 'An error occurred.',
        };
    }
}
