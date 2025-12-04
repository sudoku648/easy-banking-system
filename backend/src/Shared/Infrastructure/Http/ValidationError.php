<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

/**
 * Represents a single validation error with path and message.
 */
final readonly class ValidationError
{
    public function __construct(
        private string $path,
        private string $message,
        private mixed $invalidValue = null,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getInvalidValue(): mixed
    {
        return $this->invalidValue;
    }

    /**
     * @return array{path: string, message: string, invalidValue?: mixed}
     */
    public function toArray(): array
    {
        $data = [
            'path' => $this->path,
            'message' => $this->message,
        ];

        if (null !== $this->invalidValue && '' !== $this->invalidValue) {
            $data['invalidValue'] = $this->invalidValue;
        }

        return $data;
    }
}
