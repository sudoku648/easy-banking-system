<?php

declare(strict_types=1);

namespace App\Tests\Presentation\Transaction\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Tests\Presentation\PresentationTestCase;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId;
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
        $this->assertPageContains('Deposit');
    }

    // Deposit Form Rendering Tests

    public function testDepositFormRendersCorrectly(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        // Create an account for the customer
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
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
                customerId: $customer1->id->getValue(),
                currency: 'PLN',
            ),
        );

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer2->id->getValue(),
                currency: 'EUR',
            ),
        );

        $this->loginAsEmployeeUser($employee);
        $this->client->request('GET', '/employee/transaction/deposit');

        $this->assertResponseIsSuccessful();

        // Verify accounts are shown
        $customerId1 = CustomerId::fromString($customer1->id->getValue());
        $customerId2 = CustomerId::fromString($customer2->id->getValue());
        $accounts1 = $this->bankAccountRepository->findByCustomerId($customerId1);
        $accounts2 = $this->bankAccountRepository->findByCustomerId($customerId2);

        foreach ($accounts1 as $account) {
            if ($account->isActive) {
                $this->assertPageContains($account->iban->getValue());
            }
        }

        foreach ($accounts2 as $account) {
            if ($account->isActive) {
                $this->assertPageContains($account->iban->getValue());
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
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        // Get account
        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
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
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        // Initial balance should be 0
        self::assertSame(0, $account->balance->getAmount());

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
            'deposit_money_form[amount]' => '100.00',
        ]);

        $this->client->submit($form);

        // Verify balance updated
        $accountAfter = $this->bankAccountRepository->findById($account->id);
        self::assertNotNull($accountAfter);
        self::assertSame(10000, $accountAfter->balance->getAmount()); // 100.00 in cents
    }

    public function testDepositCreatesTransaction(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
            'deposit_money_form[amount]' => '50.00',
        ]);

        $this->client->submit($form);

        // Verify transaction created
        $transactions = $this->transactionRepository->findByBankAccountId(
            BankAccountId::fromString($account->id->getValue()),
        );

        self::assertCount(1, $transactions);
        self::assertSame(5000, $transactions[0]->amount->getAmount());
        self::assertSame('CASH_DEPOSIT', $transactions[0]->type->value);
    }

    public function testDepositWithZeroAmountShowsError(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
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
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
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
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);

        // First deposit
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');
        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
            'deposit_money_form[amount]' => '100.00',
        ]);
        $this->client->submit($form);

        // Second deposit
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');
        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
            'deposit_money_form[amount]' => '50.00',
        ]);
        $this->client->submit($form);

        // Verify accumulated balance
        $accountAfter = $this->bankAccountRepository->findById($account->id);
        self::assertNotNull($accountAfter);
        self::assertSame(15000, $accountAfter->balance->getAmount()); // 150.00 in cents
    }

    public function testDepositIntoEurAccount(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'EUR',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');

        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
            'deposit_money_form[amount]' => '75.25',
        ]);

        $this->client->submit($form);

        // Verify balance in EUR
        $accountAfter = $this->bankAccountRepository->findById($account->id);
        self::assertNotNull($accountAfter);
        self::assertSame(7525, $accountAfter->balance->getAmount()); // 75.25 EUR in cents
        self::assertSame('EUR', $accountAfter->balance->getCurrency()->value);
    }

    // Transaction History Tests

    public function testUnauthenticatedUserCannotAccessHistorySelect(): void
    {
        $this->client->request('GET', '/employee/transaction/history/select');

        $this->assertRedirectsToRoute('login');
    }

    public function testCustomerCannotAccessHistorySelect(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/employee/transaction/history/select');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEmployeeCanAccessHistorySelect(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/transaction/history/select');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Select Customer');
    }

    public function testHistorySelectFormRendersCorrectly(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/history/select');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('View History')->form();
        self::assertNotNull($form->get('select_customer_for_history_form[customerId]'));
        self::assertNotNull($form->get('select_customer_for_history_form[bankAccountId]'));
    }

    public function testHistorySelectFormShowsAllCustomers(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer1 = $this->createCustomer('customer1', 'pass123');
        $customer2 = $this->createCustomer('customer2', 'pass123');

        $this->loginAsEmployeeUser($employee);
        $this->client->request('GET', '/employee/transaction/history/select');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains($customer1->firstName->getValue());
        $this->assertPageContains($customer2->firstName->getValue());
    }

    public function testHistorySelectFormAccountsHaveCustomerIdAttribute(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        // Create account
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/history/select');

        $this->assertResponseIsSuccessful();

        // Check that account options have data-customer-id attribute
        $accountOptions = $crawler->filter('#select_customer_for_history_form_bankAccountId option[value="' . $account->id->getValue() . '"]');
        self::assertCount(1, $accountOptions);

        $option = $accountOptions->first();
        self::assertEquals($customer->id->getValue(), $option->attr('data-customer-id'));
    }

    public function testHistorySelectFormIncludesJavaScriptFile(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/transaction/history/select');

        $this->assertResponseIsSuccessful();

        // Check that the JavaScript file is included
        $scripts = $crawler->filter('script[src*="customer-account-selector.js"]');
        self::assertGreaterThan(0, $scripts->count(), 'Expected JavaScript file to be included');
    }

    public function testViewHistoryByCustomerId(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        // Create account and make deposit
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        // Make a deposit to create transaction
        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');
        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
            'deposit_money_form[amount]' => '100.00',
        ]);
        $this->client->submit($form);

        // Now view history by customer ID
        $this->client->request('GET', '/employee/transaction/history/view', [
            'customerId' => $customer->id->getValue(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Customer Transaction History');
        $this->assertPageContains($account->iban->getValue());
        $this->assertPageContains('100.00');
    }

    public function testViewHistoryByBankAccountId(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        // Make a deposit
        $this->loginAsEmployeeUser($employee);
        $crawler = $this->client->request('GET', '/employee/transaction/deposit');
        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $account->id->getValue(),
            'deposit_money_form[amount]' => '50.00',
        ]);
        $this->client->submit($form);

        // View history by bank account ID
        $this->client->request('GET', '/employee/transaction/history/view', [
            'bankAccountId' => $account->id->getValue(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Customer Transaction History');
        $this->assertPageContains($account->iban->getValue());
        $this->assertPageContains('50.00');
    }

    public function testViewHistoryWithoutParametersRedirects(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/transaction/history/view');

        $this->assertResponseRedirects('/employee/transaction/history/select');
    }

    public function testViewHistoryForCustomerWithMultipleAccounts(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        // Create two accounts
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'EUR',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);

        // Make deposits to both accounts
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/transaction/deposit');
        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $accounts[0]->id->getValue(),
            'deposit_money_form[amount]' => '100.00',
        ]);
        $this->client->submit($form);

        $crawler = $this->client->request('GET', '/employee/transaction/deposit');
        $form = $crawler->selectButton('Deposit')->form([
            'deposit_money_form[bankAccountId]' => $accounts[1]->id->getValue(),
            'deposit_money_form[amount]' => '50.00',
        ]);
        $this->client->submit($form);

        // View history for customer
        $this->client->request('GET', '/employee/transaction/history/view', [
            'customerId' => $customer->id->getValue(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertPageContains($accounts[0]->iban->getValue());
        $this->assertPageContains($accounts[1]->iban->getValue());
        $this->assertPageContains('100.00');
        $this->assertPageContains('50.00');
    }

    public function testViewHistoryShowsNoTransactionsForNewAccount(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/transaction/history/view', [
            'customerId' => $customer->id->getValue(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('No transactions');
    }
}
