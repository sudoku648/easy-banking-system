<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class InternalServerErrorResponseTest extends TestCase
{
    public function testCreateInternalServerErrorResponseWithDefaultMessage(): void
    {
        $response = new InternalServerErrorResponse();
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $jsonResponse->getStatusCode());
        self::assertSame('error', $response->getStatus());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('An unexpected error occurred', $content['message']);
        self::assertArrayNotHasKey('errors', $content);
    }

    public function testCreateInternalServerErrorResponseWithCustomMessage(): void
    {
        $response = new InternalServerErrorResponse('Database connection failed');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Database connection failed', $content['message']);
    }

    public function testCreateInternalServerErrorResponseWithErrors(): void
    {
        $errors = [
            'system' => 'Critical system failure',
        ];

        $response = new InternalServerErrorResponse('System error', $errors);
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('System error', $content['message']);
        self::assertArrayHasKey('errors', $content);
        self::assertSame($errors, $content['errors']);
    }

    public function testStatusCodeIs500(): void
    {
        $response = new InternalServerErrorResponse();
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(500, $jsonResponse->getStatusCode());
    }
}
