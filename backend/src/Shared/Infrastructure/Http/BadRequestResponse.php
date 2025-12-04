<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Response;

final class BadRequestResponse extends ApiErrorResponse
{
    /**
     * @param array<ValidationError> $validationErrors
     */
    public function __construct(
        string $message = 'Validation failed',
        ?array $errors = null,
    ) {
        $errors = ApiValidator::errorsToArray($errors);

        parent::__construct(
            message: $message,
            statusCode: Response::HTTP_BAD_REQUEST,
            errors: $this->formatErrors($errors),
        );
    }

    /**
     * @param array<array{path: string, message: string, invalidValue?: mixed}> $errors
     * @return array<string, mixed>
     */
    private function formatErrors(array $errors): array
    {
        $formatted = [];

        foreach ($errors as $error) {
            $formatted[$error['path']] = [
                'message' => $error['message'],
            ];

            if (isset($error['invalidValue'])) {
                $formatted[$error['path']]['invalidValue'] = $error['invalidValue'];
            }
        }

        return $formatted;
    }

    /**
     * Creates a validation error response from a ValidationException.
     */
    public static function fromException(ValidationException $exception): self
    {
        return new self(
            errors: $exception->getErrors(),
        );
    }
}
