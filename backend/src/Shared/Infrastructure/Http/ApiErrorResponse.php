<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorResponse extends ApiResponse
{
    /**
     * @param array<string, string>|null $errors
     */
    public function __construct(
        private readonly string $message,
        private readonly int $statusCode = Response::HTTP_BAD_REQUEST,
        private readonly ?array $errors = null,
    ) {
        $this->status = 'error';
    }

    public function toJsonResponse(): JsonResponse
    {
        return new JsonResponse(
            $this->toArray(),
            $this->statusCode,
        );
    }

    protected function toArray(): array
    {
        $response = parent::toArray();
        $response['message'] = $this->message;

        if (null !== $this->errors) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }
}
