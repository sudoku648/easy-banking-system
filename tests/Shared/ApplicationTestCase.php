<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Infrastructure\Persistence\Repository\DbalBankAccountRepository;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Infrastructure\Event\SymfonyMessengerEventBus;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\Transaction\Infrastructure\Persistence\Repository\DbalTransactionRepository;
use App\Transaction\Infrastructure\Provider\StaticExchangeRateProvider;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Infrastructure\Persistence\Repository\DbalUserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base test case for application tests that can run in both functional (in-memory)
 * and integration (real database) modes.
 *
 * The test mode is determined by the PHPUnit test suite name:
 * - "functional" suite: uses in-memory repositories
 * - "integration" suite: uses real database repositories with cleanup
 */
abstract class ApplicationTestCase extends KernelTestCase
{
    private bool $useRealDatabase = false;
    private ?Connection $connection = null;

    protected function setUp(): void
    {
        // Detect if we're running in integration mode based on environment or test suite
        $this->useRealDatabase = $this->isIntegrationTest();
        
        self::bootKernel(['environment' => 'test']);
        
        // If integration mode, override container services to use real implementations
        if ($this->useRealDatabase) {
            $this->setupRealRepositories();
            $this->connection = self::getContainer()->get(Connection::class);
            $this->cleanDatabase();
        }
    }

    protected function tearDown(): void
    {
        // Clean up in-memory repositories
        $container = self::getContainer();
        
        if ($this->useRealDatabase) {
            $this->cleanDatabase();
        } else {
            // Clean in-memory implementations
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
        }
        
        parent::tearDown();
    }

    private function isIntegrationTest(): bool
    {
        // Check if INTEGRATION_TESTS environment variable is set
        if (getenv('INTEGRATION_TESTS') === '1' || ($_SERVER['INTEGRATION_TESTS'] ?? null) === '1') {
            return true;
        }
        
        // Check PHPUnit's command line arguments
        if (isset($GLOBALS['argv'])) {
            foreach ($GLOBALS['argv'] as $arg) {
                if ($arg === '--testsuite=integration' || str_contains($arg, '--testsuite=integration')) {
                    return true;
                }
            }
        }
        
        // Check PHPUnit configuration if available (set via environment or global state)
        if (isset($_ENV['PHPUNIT_TESTSUITE']) && $_ENV['PHPUNIT_TESTSUITE'] === 'integration') {
            return true;
        }
        
        return false;
    }

    private function setupRealRepositories(): void
    {
        $container = self::getContainer();
        
        // Override services to use real implementations
        $container->set(
            UserRepositoryInterface::class,
            $container->get(DbalUserRepository::class),
        );
        
        $container->set(
            BankAccountRepositoryInterface::class,
            $container->get(DbalBankAccountRepository::class),
        );
        
        $container->set(
            TransactionRepositoryInterface::class,
            $container->get(DbalTransactionRepository::class),
        );
        
        $container->set(
            ExchangeRateProviderInterface::class,
            $container->get(StaticExchangeRateProvider::class),
        );
        
        $container->set(
            EventBus::class,
            $container->get(SymfonyMessengerEventBus::class),
        );
    }

    private function cleanDatabase(): void
    {
        if ($this->connection === null) {
            return;
        }
        
        $this->connection->executeStatement('TRUNCATE TABLE "transaction" CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE bank_account CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE "user" CASCADE');
    }

    /**
     * Helper method to ensure a customer exists in the database when running integration tests.
     * In functional mode with in-memory repositories, this is a no-op.
     * In integration mode with real database, this creates the customer if needed.
     */
    protected function ensureCustomerExists(string $customerId): void
    {
        if (!$this->useRealDatabase) {
            return;
        }

        $userRepository = self::getContainer()->get(UserRepositoryInterface::class);
        $userId = new \App\UserManagement\Domain\ValueObject\UserId($customerId);
        
        // Check if customer already exists
        if ($userRepository->findById($userId) !== null) {
            return;
        }

        // Create a dummy customer for testing
        $customer = \App\UserManagement\Domain\Entity\Customer::create(
            id: $userId,
            username: new \App\UserManagement\Domain\ValueObject\Username('customer_' . substr($customerId, 0, 8)),
            password: new \App\UserManagement\Domain\ValueObject\HashedPassword('$2y$10$test'),
            firstName: new \App\UserManagement\Domain\ValueObject\FirstName('Test'),
            lastName: new \App\UserManagement\Domain\ValueObject\LastName('Customer'),
        );

        $userRepository->save($customer);
    }
}
