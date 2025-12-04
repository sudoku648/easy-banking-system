<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ApiSuccessResponseTest extends TestCase
{
    public function testCreateSuccessResponseWithMessageOnly(): void
    {
        $response = new ApiSuccessResponse('Operation completed successfully');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_OK, $jsonResponse->getStatusCode());
        self::assertSame('success', $response->getStatus());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('success', $content['status']);
        self::assertSame('Operation completed successfully', $content['message']);
        self::assertArrayNotHasKey('data', $content);
    }

    public function testCreateSuccessResponseWithData(): void
    {
        $data = [
            'userId' => '123',
            'email' => 'test@example.com',
        ];

        $response = new ApiSuccessResponse('User created successfully', $data);
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_OK, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('success', $content['status']);
        self::assertSame('User created successfully', $content['message']);
        self::assertArrayHasKey('data', $content);
        self::assertSame($data, $content['data']);
    }

    public function testCreateSuccessResponseWithCustomStatusCode(): void
    {
        $response = new ApiSuccessResponse(
            'Resource created',
            ['id' => '456'],
            Response::HTTP_CREATED,
        );
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_CREATED, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('success', $content['status']);
        self::assertSame('Resource created', $content['message']);
    }

    public function testCreateSuccessResponseWithEmptyData(): void
    {
        $response = new ApiSuccessResponse('Operation completed', []);
        $jsonResponse = $response->toJsonResponse();

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertArrayHasKey('data', $content);
        self::assertEmpty($content['data']);
    }

    public function testCreateSuccessResponseWithNestedData(): void
    {
        $data = [
            'user' => [
                'id' => '123',
                'profile' => [
                    'name' => 'John Doe',
                    'age' => 30,
                ],
            ],
            'settings' => [
                'theme' => 'dark',
                'notifications' => true,
            ],
        ];

        $response = new ApiSuccessResponse('Data retrieved', $data);
        $jsonResponse = $response->toJsonResponse();

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame($data, $content['data']);
    }
}
