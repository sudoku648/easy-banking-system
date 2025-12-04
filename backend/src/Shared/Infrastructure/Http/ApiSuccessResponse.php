<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiSuccessResponse extends ApiResponse
{
    /**
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        private readonly string $message,
        private readonly ?array $data = null,
        private readonly int $statusCode = Response::HTTP_OK,
    ) {
        $this->status = 'success';
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

        if (null !== $this->data) {
            $response['data'] = $this->data;
        }

        return $response;
    }
}
