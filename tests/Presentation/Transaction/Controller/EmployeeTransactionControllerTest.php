<?php

declare(strict_types=1);

namespace App\Tests\Presentation\Transaction\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\Tests\Presentation\PresentationTestCase;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class EmployeeTransactionControllerTest extends PresentationTestCase
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

    public function testUnauthenticatedUserCannotAccessDepositPage(): void
    {
        $this->client->request('GET', '/employee/transaction/deposit');

        $this->assertRedirectsToRoute('login');
    }

    public function testCustomerCannotAccessDepositPage(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/employee/transaction/deposit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEmployeeCanAccessDepositPage(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/transaction/deposit');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Deposit Money');
    }

    // Deposit Form Rendering Tests

    public function testDepositFormRendersCorrectly(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        // Create an account for the customer
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $this->assertResponseIsSuccessful();

        // Check form fields exist
        $form = $crawler->selectButton('Deposit')->form();
        self::assertNotNull($form->get('deposit_money_form[bankAccountId]'));
        self::assertNotNull($form->get('deposit_money_form[amount]'));
    }

    public function testDepositFormShowsAllActiveAccounts(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
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
                currency: 'EUR',
            ),
        );

        $this->loginAsEmployeeUser($employee);
        $this->client->request('GET', '/employee/transaction/deposit');

        $this->assertResponseIsSuccessful();

        // Verify accounts are shown
        $customerId1 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer1->getId()->getValue());
        $customerId2 = new \App\BankAccount\Domain\ValueObject\CustomerId($customer2->getId()->getValue());
        $accounts1 = $this->bankAccountRepository->findByCustomerId($customerId1);
        $accounts2 = $this->bankAccountRepository->findByCustomerId($customerId2);

        foreach ($accounts1 as $account) {
            if ($account->isActive()) {
                $this->assertPageContains($account->getIban()->getValue());
            }
        }

        foreach ($accounts2 as $account) {
            if ($account->isActive()) {
                $this->assertPageContains($account->getIban()->getValue());
            }
        }
    }

    // Deposit Money Tests

    public function testDepositMoneySuccessfully(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        // Create account
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        // Get account
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->getId()->getValue(),
            'deposit_money_form[amount]' => '250.50',
        ]);

        $this->client->submit($form);

        // Should redirect after successful deposit
        $this->assertResponseRedirects('/employee/dashboard');
        $this->client->followRedirect();

        // Check for success message
        $this->assertPageContains('Cash deposited successfully');
    }

    public function testDepositUpdatesAccountBalance(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        // Initial balance should be 0
        self::assertSame(0, $account->getBalance()->getAmount());

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->getId()->getValue(),
            'deposit_money_form[amount]' => '100.00',
        ]);

        $this->client->submit($form);

        // Verify balance updated
        $accountAfter = $this->bankAccountRepository->findById($account->getId());
        self::assertNotNull($accountAfter);
        self::assertSame(10000, $accountAfter->getBalance()->getAmount()); // 100.00 in cents
    }

    public function testDepositCreatesTransaction(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->getId()->getValue(),
            'deposit_money_form[amount]' => '50.00',
        ]);

        $this->client->submit($form);

        // Verify transaction created
        $transactions = $this->transactionRepository->findByBankAccountId(
            new \App\Transaction\Domain\ValueObject\BankAccountId($account->getId()->getValue()),
        );

        self::assertCount(1, $transactions);
        self::assertSame(5000, $transactions[0]->getAmount()->getAmount());
        self::assertSame('CASH_DEPOSIT', $transactions[0]->getType()->value);
    }

    public function testDepositWithZeroAmountShowsError(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->getId()->getValue(),
            'deposit_money_form[amount]' => '0',
        ]);

        $this->client->submit($form);

        // Form validation error returns 422
        $this->assertResponseIsUnprocessable();
        $this->assertPageContains('This value should be positive');
    }

    public function testDepositWithNegativeAmountShowsError(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->getId()->getValue(),
            'deposit_money_form[amount]' => '-50.00',
        ]);

        $this->client->submit($form);

        // Form validation error returns 422
        $this->assertResponseIsUnprocessable();
        $this->assertPageContains('This value should be positive');
    }

    public function testMultipleDepositsAccumulate(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);

        // First deposit
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');
        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->getId()->getValue(),
            'deposit_money_form[amount]' => '100.00',
        ]);
        $this->client->submit($form);

        // Second deposit
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');
        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->getId()->getValue(),
            'deposit_money_form[amount]' => '50.00',
        ]);
        $this->client->submit($form);

        // Verify accumulated balance
        $accountAfter = $this->bankAccountRepository->findById($account->getId());
        self::assertNotNull($accountAfter);
        self::assertSame(15000, $accountAfter->getBalance()->getAmount()); // 150.00 in cents
    }

    public function testDepositIntoEurAccount(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'EUR',
            ),
        );

        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->getId()->getValue(),
            'deposit_money_form[amount]' => '75.25',
        ]);

        $this->client->submit($form);

        // Verify balance in EUR
        $accountAfter = $this->bankAccountRepository->findById($account->getId());
        self::assertNotNull($accountAfter);
        self::assertSame(7525, $accountAfter->getBalance()->getAmount()); // 75.25 EUR in cents
        self::assertSame('EUR', $accountAfter->getBalance()->getCurrency()->value);
    }
}
