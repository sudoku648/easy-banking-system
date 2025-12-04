<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Exception thrown when validation fails.
 */
final class ValidationException extends \RuntimeException
{
    /**
     * @param array<ValidationError> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed',
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<ValidationError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<array{path: string, message: string, invalidValue?: mixed}>
     */
    public function getErrorsAsArray(): array
    {
        return ApiValidator::errorsToArray($this->errors);
    }

    /**
     * @return array<string, string>
     */
    public function getErrorsAsFlatArray(): array
    {
        return ApiValidator::errorsToFlatArray($this->errors);
    }

    /**
     * Creates ValidationException from Symfony ConstraintViolationList.
     */
    public static function fromConstraintViolationList(
        ConstraintViolationListInterface $violations
    ): self {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = new ValidationError(
                path: $violation->getPropertyPath(),
                message: $violation->getMessage(),
                invalidValue: $violation->getInvalidValue(),
            );
        }

        return new self($errors);
    }
}
