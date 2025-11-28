<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Response;

final class InternalServerErrorResponse extends ApiErrorResponse
{
    /**
     * @param array<string, string>|null $errors
     */
    public function __construct(
        string $message = 'An unexpected error occurred',
        ?array $errors = null,
    ) {
        parent::__construct(
            message: $message,
            statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
            errors: $errors,
        );
    }
}
