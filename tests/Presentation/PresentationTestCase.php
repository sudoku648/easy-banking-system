<?php

declare(strict_types=1);

namespace App\Tests\Presentation;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\Shared\Domain\Event\EventBus;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Entity\Employee;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\FirstName;
use App\UserManagement\Domain\ValueObject\HashedPassword;
use App\UserManagement\Domain\ValueObject\LastName;
use App\UserManagement\Domain\ValueObject\UserId;
use App\UserManagement\Domain\ValueObject\Username;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base test case for presentation layer tests.
 * Provides helpers for authentication, form interaction, and UI assertions.
 * Uses in-memory repositories like functional tests.
 */
abstract class PresentationTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Disable kernel reboot to keep in-memory repositories state across requests
        $this->client->disableReboot();
        // Get container from the client to ensure we use the same one
        $this->userRepository = $this->client->getContainer()->get(UserRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        // Clean up in-memory repositories after each test
        $container = $this->client->getContainer();
        
        $repositories = [
            UserRepositoryInterface::class,
            BankAccountRepositoryInterface::class,
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
        
        $customer = Customer::create(
            id: UserId::generate(),
            username: new Username($username),
            password: new HashedPassword('temp'),
            firstName: new FirstName($firstName),
            lastName: new LastName($lastName),
        );

        // Hash the password properly using Symfony's hasher
        $hashedPassword = $hasher->hashPassword(
            new \App\UserManagement\Infrastructure\Security\SecurityUser($customer),
            $password,
        );
        
        $customer = Customer::create(
            id: $customer->getId(),
            username: $customer->getUsername(),
            password: new HashedPassword($hashedPassword),
            firstName: $customer->getFirstName(),
            lastName: $customer->getLastName(),
        );

        $repository->save($customer);

        return $customer;
    }

    /**
     * Create an employee user for testing.
     */
    protected function createEmployee(
        string $username = 'testemployee',
        string $password = 'password123',
        string $firstName = 'Admin',
        string $lastName = 'User',
    ): Employee {
        $container = $this->client->getContainer();
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $repository = $container->get(UserRepositoryInterface::class);
        
        $employee = Employee::create(
            id: UserId::generate(),
            username: new Username($username),
            password: new HashedPassword('temp'),
            firstName: new FirstName($firstName),
            lastName: new LastName($lastName),
        );

        // Hash the password properly using Symfony's hasher
        $hashedPassword = $hasher->hashPassword(
            new \App\UserManagement\Infrastructure\Security\SecurityUser($employee),
            $password,
        );
        
        $employee = Employee::create(
            id: $employee->getId(),
            username: $employee->getUsername(),
            password: new HashedPassword($hashedPassword),
            firstName: $employee->getFirstName(),
            lastName: $employee->getLastName(),
        );

        $repository->save($employee);

        return $employee;
    }

    /**
     * Log in as a specific user using the test authentication system.
     */
    protected function loginAs(string $username, string $password): Crawler
    {
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => $username,
            '_password' => $password,
        ]);

        $this->client->submit($form);
        
        // Follow redirects if any
        if ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
        }

        return $this->client->getCrawler();
    }

    /**
     * Log in as customer.
     */
    protected function loginAsCustomer(Customer $customer, string $password = 'password123'): Crawler
    {
        return $this->loginAs($customer->getUsername()->getValue(), $password);
    }

    /**
     * Log in as employee.
     */
    protected function loginAsEmployee(Employee $employee, string $password = 'password123'): Crawler
    {
        return $this->loginAs($employee->getUsername()->getValue(), $password);
    }

    /**
     * Log in as customer using Symfony's test login system (bypasses form submission).
     */
    protected function loginAsCustomerUser(Customer $customer): void
    {
        $securityUser = new \App\UserManagement\Infrastructure\Security\SecurityUser($customer);
        $this->client->loginUser($securityUser, 'main');
    }

    /**
     * Log in as employee using Symfony's test login system (bypasses form submission).
     */
    protected function loginAsEmployeeUser(Employee $employee): void
    {
        $securityUser = new \App\UserManagement\Infrastructure\Security\SecurityUser($employee);
        $this->client->loginUser($securityUser, 'main');
    }

    /**
     * Assert that the response contains flash message of specific type.
     */
    protected function assertHasFlashMessage(string $type, ?string $messageSubstring = null): void
    {
        $crawler = $this->client->getCrawler();
        $flashMessages = $crawler->filter(".alert.alert-{$type}");
        
        self::assertGreaterThan(
            0,
            $flashMessages->count(),
            "Expected to find flash message of type '{$type}', but none found.",
        );

        if ($messageSubstring !== null) {
            $messageText = $flashMessages->text();
            self::assertStringContainsString(
                $messageSubstring,
                $messageText,
                "Flash message does not contain expected text '{$messageSubstring}'.",
            );
        }
    }

    /**
     * Assert that user is on specific route.
     */
    protected function assertOnRoute(string $routeName): void
    {
        $currentPath = $this->client->getRequest()->getPathInfo();
        $router = static::getContainer()->get('router');
        $expectedPath = $router->generate($routeName);
        
        self::assertSame(
            $expectedPath,
            $currentPath,
            "Expected to be on route '{$routeName}' ({$expectedPath}), but current path is '{$currentPath}'.",
        );
    }

    /**
     * Assert that response is a redirect to specific route.
     */
    protected function assertRedirectsToRoute(string $routeName): void
    {
        self::assertTrue(
            $this->client->getResponse()->isRedirect(),
            'Expected response to be a redirect, but it is not.',
        );

        $router = static::getContainer()->get('router');
        $expectedPath = $router->generate($routeName);
        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        
        self::assertStringEndsWith(
            $expectedPath,
            $redirectUrl,
            "Expected redirect to route '{$routeName}' ({$expectedPath}), but got '{$redirectUrl}'.",
        );
    }

    /**
     * Assert that page contains specific text.
     */
    protected function assertPageContains(string $text): void
    {
        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString($text, $content);
    }

    /**
     * Assert that page does not contain specific text.
     */
    protected function assertPageNotContains(string $text): void
    {
        $content = $this->client->getResponse()->getContent();
        self::assertStringNotContainsString($text, $content);
    }

    /**
     * Assert that form field exists with specific value.
     */
    protected function assertFormFieldExists(Crawler $crawler, string $fieldName, ?string $expectedValue = null): void
    {
        $field = $crawler->filter("[name=\"{$fieldName}\"]");
        
        self::assertGreaterThan(
            0,
            $field->count(),
            "Expected form field '{$fieldName}' to exist, but it was not found.",
        );

        if ($expectedValue !== null) {
            $actualValue = $field->attr('value');
            self::assertSame(
                $expectedValue,
                $actualValue,
                "Expected form field '{$fieldName}' to have value '{$expectedValue}', but got '{$actualValue}'.",
            );
        }
    }

    /**
     * Assert that the current page title contains specific text.
     */
    protected function assertPageTitleMatches(string $text): void
    {
        $crawler = $this->client->getCrawler();
        $title = $crawler->filter('title')->text();
        self::assertStringContainsString($text, $title);
    }
}
