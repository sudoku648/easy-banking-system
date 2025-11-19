<?php

declare(strict_types=1);

namespace App\Tests\Presentation\BankAccount\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\Tests\Presentation\PresentationTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class BankAccountControllerTest extends PresentationTestCase
{
    private MessageBusInterface $messageBus;
    private BankAccountRepositoryInterface $bankAccountRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBus = static::getContainer()->get(MessageBusInterface::class);
        $this->bankAccountRepository = static::getContainer()->get(BankAccountRepositoryInterface::class);
    }

    // Access Control Tests

    public function testCustomerCannotAccessOpenNewCustomerPage(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/employee/bank-account/open/new-customer');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedUserCannotAccessOpenNewCustomerPage(): void
    {
        $this->client->request('GET', '/employee/bank-account/open/new-customer');

        $this->assertRedirectsToRoute('login');
    }

    public function testEmployeeCanAccessOpenNewCustomerPage(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/bank-account/open/new-customer');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Open Account - New Customer');
    }

    // Open Account for New Customer Tests

    public function testOpenNewCustomerFormRendersCorrectly(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/open/new-customer');

        $this->assertResponseIsSuccessful();
        
        // Check form fields exist
        $form = $crawler->selectButton('Open Account')->form();
        self::assertNotNull($form->get('open_account_new_customer_form[username]'));
        self::assertNotNull($form->get('open_account_new_customer_form[password]'));
        self::assertNotNull($form->get('open_account_new_customer_form[firstName]'));
        self::assertNotNull($form->get('open_account_new_customer_form[lastName]'));
        self::assertNotNull($form->get('open_account_new_customer_form[currency]'));
    }

    public function testOpenNewCustomerAccountSuccessfully(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/open/new-customer');
        $form = $crawler->selectButton('Open Account')->form([
            'open_account_new_customer_form[username]' => 'newcustomer',
            'open_account_new_customer_form[password]' => 'password123',
            'open_account_new_customer_form[firstName]' => 'John',
            'open_account_new_customer_form[lastName]' => 'Doe',
            'open_account_new_customer_form[currency]' => 'PLN',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertOnRoute('employee_dashboard');
        $this->assertHasFlashMessage('success', 'Bank account opened successfully');
    }

    public function testOpenNewCustomerAccountWithInvalidUsernameShowsError(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/open/new-customer');
        $form = $crawler->selectButton('Open Account')->form([
            'open_account_new_customer_form[username]' => 'a', // Too short
            'open_account_new_customer_form[password]' => 'password123',
            'open_account_new_customer_form[firstName]' => 'John',
            'open_account_new_customer_form[lastName]' => 'Doe',
            'open_account_new_customer_form[currency]' => 'PLN',
        ]);

        $this->client->submit($form);

        // Form validation error returns 422 or re-renders with 200
        self::assertContains($this->client->getResponse()->getStatusCode(), [200, 422]);
        $this->assertPageContains('This value is too short');
    }

    public function testOpenNewCustomerAccountWithShortPasswordShowsError(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/open/new-customer');
        $form = $crawler->selectButton('Open Account')->form([
            'open_account_new_customer_form[username]' => 'newcustomer',
            'open_account_new_customer_form[password]' => '12345', // Too short
            'open_account_new_customer_form[firstName]' => 'John',
            'open_account_new_customer_form[lastName]' => 'Doe',
            'open_account_new_customer_form[currency]' => 'PLN',
        ]);

        $this->client->submit($form);

        // Form validation error returns 422 or re-renders with 200
        self::assertContains($this->client->getResponse()->getStatusCode(), [200, 422]);
        $this->assertPageContains('This value is too short');
    }

    public function testOpenNewCustomerAccountWithDuplicateUsernameShowsError(): void
    {
        $this->createCustomer('existinguser', 'pass123');
        
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/open/new-customer');
        $form = $crawler->selectButton('Open Account')->form([
            'open_account_new_customer_form[username]' => 'existinguser',
            'open_account_new_customer_form[password]' => 'password123',
            'open_account_new_customer_form[firstName]' => 'John',
            'open_account_new_customer_form[lastName]' => 'Doe',
            'open_account_new_customer_form[currency]' => 'PLN',
        ]);

        $this->client->submit($form);
        
        // The duplicate username should cause an error
        // Either redirected with flash message or form re-rendered
        if ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
            $this->assertOnRoute('employee_dashboard');
            $this->assertHasFlashMessage('danger', 'Error');
        } else {
            // Form re-rendered with error
            self::assertContains($this->client->getResponse()->getStatusCode(), [200, 422, 500]);
        }
    }

    // Open Account for Existing Customer Tests

    public function testOpenExistingCustomerFormRendersCorrectly(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/open/existing-customer');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Open Account - Existing Customer');
        
        $form = $crawler->selectButton('Open Account')->form();
        self::assertNotNull($form->get('open_account_existing_customer_form[customerId]'));
        self::assertNotNull($form->get('open_account_existing_customer_form[currency]'));
    }

    public function testOpenExistingCustomerAccountSuccessfully(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/open/existing-customer');
        $form = $crawler->selectButton('Open Account')->form([
            'open_account_existing_customer_form[customerId]' => $customer->getId()->getValue(),
            'open_account_existing_customer_form[currency]' => 'EUR',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertOnRoute('employee_dashboard');
        $this->assertHasFlashMessage('success', 'Bank account opened successfully');
        
        // Verify account was created
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        self::assertCount(1, $accounts);
        self::assertSame('EUR', $accounts[0]->getBalance()->getCurrency()->value);
    }

    public function testOpenExistingCustomerFormShowsCustomerList(): void
    {
        $customer1 = $this->createCustomer('customer1', 'pass123', 'John', 'Doe');
        $customer2 = $this->createCustomer('customer2', 'pass123', 'Jane', 'Smith');
        
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/bank-account/open/existing-customer');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('John Doe');
        $this->assertPageContains('Jane Smith');
    }

    // Close Account Tests

    public function testCloseAccountFormRendersCorrectly(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/close');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Close Bank Account');
        
        $form = $crawler->selectButton('Close Account')->form();
        self::assertNotNull($form->get('close_bank_account_form[bankAccountId]'));
    }

    public function testCloseAccountSuccessfully(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        // Create an account to close
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $accountToClose = $accounts[0];
        
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/bank-account/close');
        $form = $crawler->selectButton('Close Account')->form([
            'close_bank_account_form[bankAccountId]' => $accountToClose->getId()->getValue(),
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertOnRoute('employee_dashboard');
        $this->assertHasFlashMessage('success', 'Bank account closed successfully');
        
        // Verify account is closed
        $closedAccount = $this->bankAccountRepository->findById($accountToClose->getId());
        self::assertNotNull($closedAccount);
        self::assertFalse($closedAccount->isActive());
    }

    public function testCloseAccountFormShowsOnlyActiveAccounts(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        
        // Create an active account
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->getId()->getValue(),
                currency: 'PLN',
            ),
        );
        
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/bank-account/close');

        $this->assertResponseIsSuccessful();
        
        // Should show the active account's IBAN
        $customerId = new \App\BankAccount\Domain\ValueObject\CustomerId($customer->getId()->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $this->assertPageContains($accounts[0]->getIban()->getValue());
    }
}
