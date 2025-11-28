<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class UnprocessableEntityResponseTest extends TestCase
{
    public function testCreateUnprocessableEntityResponseWithMessage(): void
    {
        $response = new UnprocessableEntityResponse('Insufficient funds');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $jsonResponse->getStatusCode());
        self::assertSame('error', $response->getStatus());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Insufficient funds', $content['message']);
        self::assertArrayNotHasKey('errors', $content);
    }

    public function testCreateUnprocessableEntityResponseForDomainException(): void
    {
        $response = new UnprocessableEntityResponse('Bank account is closed');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Bank account is closed', $content['message']);
    }

    public function testCreateUnprocessableEntityResponseWithErrors(): void
    {
        $errors = [
            'balance' => 'Cannot process transaction with negative balance',
        ];

        $response = new UnprocessableEntityResponse('Transaction cannot be processed', $errors);
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('error', $content['status']);
        self::assertSame('Transaction cannot be processed', $content['message']);
        self::assertArrayHasKey('errors', $content);
        self::assertSame($errors, $content['errors']);
    }

    public function testStatusCodeIs422(): void
    {
        $response = new UnprocessableEntityResponse('Business rule violation');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(422, $jsonResponse->getStatusCode());
    }

    public function testCreateUnprocessableEntityResponseForBusinessRuleViolation(): void
    {
        $response = new UnprocessableEntityResponse('Daily withdrawal limit exceeded');
        $jsonResponse = $response->toJsonResponse();

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $jsonResponse->getStatusCode());

        $content = json_decode($jsonResponse->getContent(), true);
        self::assertSame('Daily withdrawal limit exceeded', $content['message']);
    }
}
