<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

abstract class ApiResponse
{
    protected string $status;

    abstract public function toJsonResponse(): JsonResponse;

    /**
     * @return array<string, mixed>
     */
    protected function toArray(): array
    {
        return [
            'status' => $this->status,
        ];
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
