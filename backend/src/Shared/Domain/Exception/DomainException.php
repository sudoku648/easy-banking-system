<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use Symfony\Component\HttpFoundation\Response;

abstract class DomainException extends \DomainException
{
    public function __construct(
        string $message = '',
        private readonly int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
