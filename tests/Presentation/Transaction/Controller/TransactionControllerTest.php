<?php

declare(strict_types=1);

namespace App\Tests\Presentation\Transaction\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\Tests\Presentation\PresentationTestCase;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class TransactionControllerTest extends PresentationTestCase
{
    private MessageBusInterface $messageBus;
    private BankAccountRepositoryInterface $bankAccountRepository;
    private TransactionRepositoryInterface $transactionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBus = static::getContainer()->get(MessageBusInterface::class);
        $this->bankAccountRepository = static::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->transactionRepository = static::getContainer()->get(TransactionRepositoryInterface::class);
    }

    // Access Control Tests

    public function testUnauthenticatedUserCannotAccessTransferPage(): void
    {
        $this->client->request('GET', '/customer/transaction/transfer');

        $this->assertRedirectsToRoute('login');
    }

    public function testEmployeeCannotAccessTransferPage(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/customer/transaction/transfer');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCustomerCanAccessTransferPage(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/customer/transaction/transfer');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Transfer Money');
    }

    // Transfer Form Rendering Tests

    public function testTransferFormRendersCorrectly(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        // Create an account for the customer
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $this->loginAsCustomerUser($customer);
        $crawler = $this->client->request('GET', '/customer/transaction/transfer');

        $this->assertResponseIsSuccessful();
        
        // Check form fields exist
        $form = $crawler->selectButton('Transfer')->form();
        self::assertNotNull($form->get('transfer_money_form[fromBankAccountId]'));
        self::assertNotNull($form->get('transfer_money_form[toIban]'));
        self::assertNotNull($form->get('transfer_money_form[amount]'));
    }

    public function testTransferFormShowsCustomerAccounts(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        // Create accounts
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
        $this->client->request('GET', '/customer/transaction/transfer');

        $this->assertResponseIsSuccessful();
        
        // Verify accounts are shown
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        
        foreach ($accounts as $account) {
            if ($account->isActive()) {
                $this->assertPageContains($account->getIban()->getValue());
            }
        }
    }

    public function testTransferFormShowsOnlyActiveAccounts(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        // Create an account
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $this->loginAsCustomerUser($customer);
        $this->client->request('GET', '/customer/transaction/transfer');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Select source account');
    }

    // Transfer Money Tests

    public function testTransferMoneySuccessfully(): void
    {
        // Create two customers with accounts
        $customer1 = $this->createCustomer('customer1', 'pass123');
        $customer2 = $this->createCustomer('customer2', 'pass123');
        
        // Create accounts
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer1->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer2->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        // Get accounts
        $customerId1 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer1->getId()->getValue());
        $customerId2 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer2->getId()->getValue());
        $account1 = $this->bankAccountRepository->findByCustomerId($customerId1)[0];
        $account2 = $this->bankAccountRepository->findByCustomerId($customerId2)[0];
        
        // Add money to account1 using deposit method
        $account1->deposit(new \App\Shared\Domain\ValueObject\Money(50000, \App\Shared\Domain\ValueObject\Currency::PLN));
        $this->bankAccountRepository->save($account1);
        
        $this->loginAsCustomerUser($customer1);
        $crawler = $this->client->request('GET', '/customer/transaction/transfer');
        
        $form = $crawler->selectButton('Transfer')->form([
            'transfer_money_form[fromBankAccountId]' => $account1->getId()->getValue(),
            'transfer_money_form[toIban]' => $account2->getIban()->getValue(),
            'transfer_money_form[amount]' => '100.00',
        ]);

        $this->client->submit($form);
        
        // Should redirect after successful transfer
        $this->assertResponseRedirects('/customer/dashboard');
        $this->client->followRedirect();
        
        // Check for success message
        $this->assertPageContains('Transfer completed successfully');
    }

    public function testTransferWithInvalidIbanShowsError(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];
        
        $this->loginAsCustomerUser($customer);
        $crawler = $this->client->request('GET', '/customer/transaction/transfer');
        
        $form = $crawler->selectButton('Transfer')->form([
            'transfer_money_form[fromBankAccountId]' => $account->getId()->getValue(),
            'transfer_money_form[toIban]' => 'INVALID', // Invalid IBAN
            'transfer_money_form[amount]' => '10.00',
        ]);

        $this->client->submit($form);

        // Form validation error returns 422
        $this->assertResponseIsUnprocessable();
        $this->assertPageContains('This value is too short');
    }

    public function testTransferWithZeroAmountShowsError(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $customer2 = $this->createCustomer('customer2', 'pass123');
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer2->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $customerId2 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer2->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];
        $account2 = $this->bankAccountRepository->findByCustomerId($customerId2)[0];
        
        $this->loginAsCustomerUser($customer);
        $crawler = $this->client->request('GET', '/customer/transaction/transfer');
        
        $form = $crawler->selectButton('Transfer')->form([
            'transfer_money_form[fromBankAccountId]' => $account->getId()->getValue(),
            'transfer_money_form[toIban]' => $account2->getIban()->getValue(),
            'transfer_money_form[amount]' => '0',
        ]);

        $this->client->submit($form);

        // Form validation error returns 422
        $this->assertResponseIsUnprocessable();
        $this->assertPageContains('This value should be positive');
    }

    public function testTransferWithNegativeAmountShowsError(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $customer2 = $this->createCustomer('customer2', 'pass123');
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer2->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $customerId2 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer2->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];
        $account2 = $this->bankAccountRepository->findByCustomerId($customerId2)[0];
        
        $this->loginAsCustomerUser($customer);
        $crawler = $this->client->request('GET', '/customer/transaction/transfer');
        
        $form = $crawler->selectButton('Transfer')->form([
            'transfer_money_form[fromBankAccountId]' => $account->getId()->getValue(),
            'transfer_money_form[toIban]' => $account2->getIban()->getValue(),
            'transfer_money_form[amount]' => '-50.00',
        ]);

        $this->client->submit($form);

        // Form validation error returns 422
        $this->assertResponseIsUnprocessable();
        $this->assertPageContains('This value should be positive');
    }

    // Transaction History Tests

    public function testUnauthenticatedUserCannotAccessTransactionHistory(): void
    {
        $this->client->request('GET', '/customer/transaction/history');

        $this->assertRedirectsToRoute('login');
    }

    public function testEmployeeCannotAccessTransactionHistory(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/customer/transaction/history');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCustomerCanAccessTransactionHistory(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/customer/transaction/history');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Transaction History');
        $this->assertPageTitleMatches('Transaction History');
    }

    public function testTransactionHistoryShowsNoTransactionsForNewCustomer(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/customer/transaction/history');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('No transactions');
    }

    public function testTransactionHistoryDisplaysTransactionDetails(): void
    {
        $customer1 = $this->createCustomer('customer1', 'pass123');
        $customer2 = $this->createCustomer('customer2', 'pass123');
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer1->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer2->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $customerId1 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer1->getId()->getValue());
        $customerId2 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer2->getId()->getValue());
        $account1 = $this->bankAccountRepository->findByCustomerId($customerId1)[0];
        $account2 = $this->bankAccountRepository->findByCustomerId($customerId2)[0];
        
        // Add money to account1 using deposit method
        $account1->deposit(new \App\Shared\Domain\ValueObject\Money(50000, \App\Shared\Domain\ValueObject\Currency::PLN));
        $this->bankAccountRepository->save($account1);
        
        // Create a transfer
        $this->loginAsCustomerUser($customer1);
        $crawler = $this->client->request('GET', '/customer/transaction/transfer');
        
        $form = $crawler->selectButton('Transfer')->form([
            'transfer_money_form[fromBankAccountId]' => $account1->getId()->getValue(),
            'transfer_money_form[toIban]' => $account2->getIban()->getValue(),
            'transfer_money_form[amount]' => '100.00',
        ]);
        
        $this->client->submit($form);
        
        // Check transaction history
        $this->client->request('GET', '/customer/transaction/history');
        
        $this->assertResponseIsSuccessful();
        $this->assertPageContains($account1->getIban()->getValue());
        $this->assertPageContains('100.00');
        $this->assertPageContains('PLN');
        $this->assertPageContains('Withdrawal');
        $this->assertPageContains(date('Y-m-d'));
    }

    public function testTransactionHistoryShowsOnlyCustomerTransactions(): void
    {
        $customer1 = $this->createCustomer('customer1', 'pass123');
        $customer2 = $this->createCustomer('customer2', 'pass123');
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer1->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer2->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $customerId1 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer1->getId()->getValue());
        $customerId2 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer2->getId()->getValue());
        $account1 = $this->bankAccountRepository->findByCustomerId($customerId1)[0];
        $account2 = $this->bankAccountRepository->findByCustomerId($customerId2)[0];
        
        // Add money to both accounts
        $account1->deposit(new \App\Shared\Domain\ValueObject\Money(50000, \App\Shared\Domain\ValueObject\Currency::PLN));
        $this->bankAccountRepository->save($account1);
        
        $account2->deposit(new \App\Shared\Domain\ValueObject\Money(30000, \App\Shared\Domain\ValueObject\Currency::PLN));
        $this->bankAccountRepository->save($account2);
        
        // Create a transfer from customer1 to customer2
        $this->loginAsCustomerUser($customer1);
        $crawler = $this->client->request('GET', '/customer/transaction/transfer');
        
        $form = $crawler->selectButton('Transfer')->form([
            'transfer_money_form[fromBankAccountId]' => $account1->getId()->getValue(),
            'transfer_money_form[toIban]' => $account2->getIban()->getValue(),
            'transfer_money_form[amount]' => '100.00',
        ]);
        
        $this->client->submit($form);
        
        // Check customer1's transaction history
        $this->client->request('GET', '/customer/transaction/history');
        $this->assertResponseIsSuccessful();
        $this->assertPageContains($account1->getIban()->getValue());
        
        // Login as customer2 and check they see their transaction
        $this->loginAsCustomerUser($customer2);
        $this->client->request('GET', '/customer/transaction/history');
        $this->assertResponseIsSuccessful();
        $this->assertPageContains($account2->getIban()->getValue());
        // Should not see customer1's account
        $this->assertPageNotContains($account1->getIban()->getValue());
    }
}
