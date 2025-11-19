<?php

declare(strict_types=1);

namespace App\Tests\Integration;

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
 * Base class for integration tests that use real database and DBAL repositories.
 *
 * This class overrides the container services to use real implementations
 * instead of in-memory test doubles.
 */
abstract class IntegrationTestCase extends KernelTestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
        
        // Override services to use real implementations
        $container = self::getContainer();
        
        // Get real repository implementations
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
        
        $this->connection = $container->get(Connection::class);
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $this->connection->executeStatement('TRUNCATE TABLE "transaction" CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE bank_account CASCADE');
        $this->connection->executeStatement('TRUNCATE TABLE "user" CASCADE');
    }
}
