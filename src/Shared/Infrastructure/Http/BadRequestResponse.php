<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Response;

final class BadRequestResponse extends ApiErrorResponse
{
    /**
     * @param array<string, string>|null $errors
     */
    public function __construct(
        string $message,
        ?array $errors = null,
    ) {
        parent::__construct(
            message: $message,
            statusCode: Response::HTTP_BAD_REQUEST,
            errors: $errors,
        );
    }
}
