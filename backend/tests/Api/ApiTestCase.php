<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\Shared\Domain\Event\EventBus;
use App\Tests\Support\AddressTestHelper;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\Locale;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base test case for API layer tests.
 * Provides helpers for JSON API testing.
 * Uses in-memory repositories like functional tests.
 */
abstract class ApiTestCase extends WebTestCase
{
    use AddressTestHelper;

    protected KernelBrowser $client;
    protected string $apiKey;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Disable kernel reboot to keep in-memory repositories state across requests
        $this->client->disableReboot();

        // Get API key from environment
        $this->apiKey = $_ENV['API_KEY'] ?? 'test_api_key_12345';
    }

    /**
     * Make an authenticated API request with API key header.
     *
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $server
     */
    protected function makeApiRequest(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
    ): void {
        $server['HTTP_X_API_KEY'] = $this->apiKey;

        $this->client->jsonRequest($method, $uri, $parameters, $server);
    }

    protected function tearDown(): void
    {
        // Clean up in-memory repositories after each test
        $container = $this->client->getContainer();

        $repositories = [
            UserRepositoryInterface::class,
            BankAccountRepositoryInterface::class,
            DebitCardRepositoryInterface::class,
            TransactionRepositoryInterface::class,
            ExchangeRateProviderInterface::class,
            EventBus::class,
        ];

        foreach ($repositories as $serviceId) {
            $service = $container->get($serviceId);
            if (method_exists($service, 'clear')) {
                $service->clear();
            }
        }

        parent::tearDown();
    }

    /**
     * Assert that response is a JSON response with specific status code.
     */
    protected function assertJsonResponse(int $statusCode = 200): void
    {
        $response = $this->client->getResponse();

        self::assertSame($statusCode, $response->getStatusCode());
        self::assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            'Response is not JSON',
        );
    }

    /**
     * Get decoded JSON response.
     *
     * @return array<string, mixed>
     */
    protected function getJsonResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        self::assertIsArray($data, 'Response is not valid JSON');

        return $data;
    }

    /**
     * Assert JSON response contains specific key-value pair.
     */
    protected function assertJsonResponseContains(string $key, mixed $value): void
    {
        $data = $this->getJsonResponse();

        self::assertArrayHasKey($key, $data);
        self::assertSame($value, $data[$key]);
    }

    /**
     * Assert JSON response has error status.
     */
    protected function assertJsonError(?string $expectedMessage = null): void
    {
        $data = $this->getJsonResponse();

        self::assertArrayHasKey('status', $data);
        self::assertSame('error', $data['status']);

        if (null !== $expectedMessage) {
            self::assertArrayHasKey('message', $data);
            self::assertStringContainsString($expectedMessage, $data['message']);
        }
    }

    /**
     * Assert JSON response has success status.
     */
    protected function assertJsonSuccess(?string $expectedMessage = null): void
    {
        $data = $this->getJsonResponse();

        self::assertArrayHasKey('status', $data);
        self::assertSame('success', $data['status']);

        if (null !== $expectedMessage) {
            self::assertArrayHasKey('message', $data);
            self::assertStringContainsString($expectedMessage, $data['message']);
        }
    }

    /**
     * Create a customer user for testing.
     */
    protected function createCustomer(
        string $username = 'testcustomer',
        string $password = 'password123',
        string $firstName = 'John',
        string $lastName = 'Doe',
    ): Customer {
        $container = $this->client->getContainer();
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $repository = $container->get(UserRepositoryInterface::class);

        $address = $this->createTestAddress();
        $correspondenceAddress = $this->createTestCorrespondenceAddress();
        $customer = Customer::create(
            id: UserId::generate(),
            username: Username::fromString($username),
            password: HashedPassword::fromString('temp'),
            firstName: FirstName::fromString($firstName),
            lastName: LastName::fromString($lastName),
            permanentResidenceAddress: $address,
            correspondenceAddress: $correspondenceAddress,
        );

        // Hash the password properly using Symfony's hasher
        $hashedPassword = $hasher->hashPassword(
            new SecurityUser($customer),
            $password,
        );

        $customer = Customer::create(
            id: $customer->id,
            username: $customer->username,
            password: HashedPassword::fromString($hashedPassword),
            firstName: $customer->firstName,
            lastName: $customer->lastName,
            permanentResidenceAddress: $address,
            correspondenceAddress: $correspondenceAddress,
        );
        $customer->changeLocale(Locale::ENGLISH);

        $repository->save($customer);

        return $customer;
    }
}
