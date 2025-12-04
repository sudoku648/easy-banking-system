<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }
}
