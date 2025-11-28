<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\ApiErrorResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ApiErrorResponseTest extends TestCase
{
    public function testCreateErrorResponseWithMessageOnly(): void
    {
        $response = new ApiErrorResponse('Something went wrong');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $jsonResponse->getStatusCode());
        self::assertSame('error', $response->getStatus());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Something went wrong', $content['message']);
        self::assertArrayNotHasKey('errors', $content);
    }

    public function testCreateErrorResponseWithCustomStatusCode(): void
    {
        $response = new ApiErrorResponse(
            'Unauthorized access',
            Response::HTTP_UNAUTHORIZED,
        );
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Unauthorized access', $content['message']);
    }

    public function testCreateErrorResponseWithValidationErrors(): void
    {
        $errors = [
            'email' => 'Invalid email format',
            'password' => 'Password must be at least 8 characters',
        ];

        $response = new ApiErrorResponse(
            'Validation failed',
            Response::HTTP_BAD_REQUEST,
            $errors,
        );
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Validation failed', $content['message']);
        self::assertArrayHasKey('errors', $content);
        self::assertSame($errors, $content['errors']);
    }

    public function testCreateErrorResponseWithEmptyErrors(): void
    {
        $response = new ApiErrorResponse(
            'Operation failed',
            Response::HTTP_BAD_REQUEST,
            [],
        );
        $jsonResponse = $response->toJsonResponse();

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertArrayHasKey('errors', $content);
        self::assertEmpty($content['errors']);
    }

    public function testCreateErrorResponseForInternalServerError(): void
    {
        $response = new ApiErrorResponse(
            'An unexpected error occurred',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('An unexpected error occurred', $content['message']);
    }

    public function testCreateErrorResponseForNotFound(): void
    {
        $response = new ApiErrorResponse(
            'Resource not found',
            Response::HTTP_NOT_FOUND,
        );
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_NOT_FOUND, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Resource not found', $content['message']);
    }
}
