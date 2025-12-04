<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\Shared\Domain\Event\EventBus;
use App\Transaction\Domain\Persistence\Repository\PendingInterbankTransferRepositoryInterface;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\Provider\ExchangeRateProviderInterface;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base test case for functional tests that use in-memory implementations.
 *
 * Functional tests use in-memory repositories and event bus for fast execution
 * without database dependencies.
 */
abstract class ApplicationTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
    }

    protected function tearDown(): void
    {
        // Clean up in-memory repositories
        $container = self::getContainer();

        $repositories = [
            UserRepositoryInterface::class,
            BankAccountRepositoryInterface::class,
            TransactionRepositoryInterface::class,
            PendingInterbankTransferRepositoryInterface::class,
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
}
