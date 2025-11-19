<?php

declare(strict_types=1);

namespace App\Tests\Presentation\UserManagement\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\Tests\Presentation\PresentationTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class CustomerDashboardControllerTest extends PresentationTestCase
{
    private MessageBusInterface $messageBus;
    private BankAccountRepositoryInterface $bankAccountRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBus = static::getContainer()->get(MessageBusInterface::class);
        $this->bankAccountRepository = static::getContainer()->get(BankAccountRepositoryInterface::class);
    }

    public function testUnauthenticatedUserCannotAccessCustomerDashboard(): void
    {
        $this->client->request('GET', '/customer/dashboard');

        $this->assertRedirectsToRoute('login');
    }

    public function testEmployeeCannotAccessCustomerDashboard(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCustomerCanAccessDashboard(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Customer Dashboard');
        $this->assertPageTitleMatches('Customer Dashboard');
    }

    public function testDashboardDisplaysNoBankAccountsForNewCustomer(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains("You don't have any bank accounts yet");
    }

    public function testDashboardDisplaysCustomerBankAccounts(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        // Create a bank account for the customer
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        $this->loginAsCustomerUser($customer);
        $crawler = $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertPageNotContains('No bank accounts');
        
        // Check that account details are displayed
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];
        
        $this->assertPageContains($account->getIban()->getValue());
        $this->assertPageContains('PLN');
        $this->assertPageContains('0.00'); // Initial balance
    }

    public function testDashboardDisplaysMultipleBankAccounts(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        // Create multiple bank accounts
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'EUR',
            ),
        );

        $this->loginAsCustomerUser($customer);
        $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('PLN');
        $this->assertPageContains('EUR');
        
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        foreach ($accounts as $account) {
            $this->assertPageContains($account->getIban()->getValue());
        }
    }

    public function testDashboardDisplaysAccountStatusActive(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        $this->loginAsCustomerUser($customer);
        $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Active');
    }

    public function testDashboardShowsNavigationToTransferMoney(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $crawler = $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseIsSuccessful();
        
        // Check that there's a link to transfer money
        $link = $crawler->selectLink('Transfer Money')->count();
        self::assertGreaterThan(0, $link, 'Expected to find "Transfer Money" link on dashboard');
    }

    public function testDashboardShowsNavigationToTransactionHistory(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $crawler = $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseIsSuccessful();
        
        // Check that there's a link to view transaction history
        $link = $crawler->selectLink('View History')->count();
        self::assertGreaterThan(0, $link, 'Expected to find "View History" link on dashboard');
    }

    public function testDashboardShowsLogoutOption(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $crawler = $this->client->request('GET', '/customer/dashboard');

        $this->assertResponseIsSuccessful();
        
        // Check logout link exists
        $link = $crawler->selectLink('Logout')->count();
        self::assertGreaterThan(0, $link, 'Expected to find "Logout" link on dashboard');
    }
}
