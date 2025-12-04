<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API Validator that provides structured validation error responses.
 *
 * Returns validation errors with their property paths and formatted messages
 * suitable for API responses.
 */
final readonly class ApiValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Validates an object and returns structured validation errors.
     *
     * @param object $object The object to validate
     * @param array<string>|null $groups Validation groups to apply
     *
     * @return array<ValidationError> Array of validation errors (empty if valid)
     */
    public function validate(object $object, ?array $groups = null): array
    {
        $violations = $this->validator->validate($object, null, $groups);

        return $this->formatViolations($violations);
    }

    /**
     * Formats constraint violations into structured ValidationError objects.
     *
     * @return array<ValidationError>
     */
    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = new ValidationError(
                path: $violation->getPropertyPath(),
                message: (string) $violation->getMessage(),
                invalidValue: $violation->getInvalidValue(),
            );
        }

        return $errors;
    }

    /**
     * Converts validation errors to a simple array format.
     *
     * @param array<ValidationError> $errors
     * @return array<array{path: string, message: string, invalidValue?: mixed}>
     */
    public static function errorsToArray(array $errors): array
    {
        return array_map(
            fn (ValidationError $error): array => $error->toArray(),
            $errors,
        );
    }

    /**
     * Converts validation errors to a flat associative array (path => message).
     *
     * @param array<ValidationError> $errors
     * @return array<string, string>
     */
    public static function errorsToFlatArray(array $errors): array
    {
        $flatErrors = [];

        foreach ($errors as $error) {
            $flatErrors[$error->getPath()] = $error->getMessage();
        }

        return $flatErrors;
    }
}
