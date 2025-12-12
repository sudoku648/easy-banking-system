<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\BadRequestResponse;
use App\Shared\Infrastructure\Http\ValidationError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class BadRequestResponseTest extends TestCase
{
    public function testCreateBadRequestResponseWithMessage(): void
    {
        $response = new BadRequestResponse('Invalid request data');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $jsonResponse->getStatusCode());
        self::assertSame('error', $response->getStatus());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Invalid request data', $content['message']);
        self::assertArrayNotHasKey('errors', $content);
    }

    public function testCreateBadRequestResponseWithValidationErrors(): void
    {
        $errors = [
            new ValidationError('email', 'Invalid email format'),
            new ValidationError('password', 'Password is too short'),
        ];

        $response = new BadRequestResponse('Validation failed', $errors);
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Validation failed', $content['message']);
        self::assertArrayHasKey('errors', $content);
    }

    public function testCreateBadRequestResponseForInvalidJson(): void
    {
        $response = new BadRequestResponse('Invalid JSON format');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('Invalid JSON format', $content['message']);
    }

    public function testStatusCodeIs400(): void
    {
        $response = new BadRequestResponse('Bad request');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(400, $jsonResponse->getStatusCode());
    }
}
