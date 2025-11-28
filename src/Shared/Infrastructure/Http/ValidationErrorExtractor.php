<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final readonly class ValidationErrorExtractor
{
    /**
     * @return array<string, string>
     */
    public static function extract(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        return $errors;
    }
}
