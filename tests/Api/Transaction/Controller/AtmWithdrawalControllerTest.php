<?php

declare(strict_types=1);

namespace App\Tests\Api\Transaction\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Tests\Api\ApiTestCase;
use App\Transaction\Domain\Persistence\Repository\TransactionRepositoryInterface;
use App\Transaction\Domain\ValueObject\BankAccountId as BankAccountIdVO;
use App\Transaction\Domain\ValueObject\TransactionType;
use Symfony\Component\Messenger\MessageBusInterface;

final class AtmWithdrawalControllerTest extends ApiTestCase
{
    private MessageBusInterface $messageBus;
    private BankAccountRepositoryInterface $bankAccountRepository;
    private DebitCardRepositoryInterface $debitCardRepository;
    private TransactionRepositoryInterface $transactionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBus = static::getContainer()->get(MessageBusInterface::class);
        $this->bankAccountRepository = static::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->debitCardRepository = static::getContainer()->get(DebitCardRepositoryInterface::class);
        $this->transactionRepository = static::getContainer()->get(TransactionRepositoryInterface::class);
    }

    // API Endpoint Tests

    public function testAtmWithdrawalWithoutApiKey(): void
    {
        // Make request without API key
        $this->client->jsonRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => '1234567890123456',
                'amount' => 100,
                'currency' => 'PLN',
            ],
        );

        $this->assertResponseStatusCodeSame(401);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertSame('API key is missing', $response['message']);
    }

    public function testAtmWithdrawalWithInvalidApiKey(): void
    {
        // Make request with invalid API key
        $this->client->jsonRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => '1234567890123456',
                'amount' => 100,
                'currency' => 'PLN',
            ],
            ['HTTP_X_API_KEY' => 'invalid_key'],
        );

        $this->assertResponseStatusCodeSame(401);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertSame('Invalid API key', $response['message']);
    }

    public function testAtmWithdrawalSuccessfully(): void
    {
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

        // Issue debit card
        $debitCard = $this->issueDebitCard($account->id);

        // Add initial balance
        $account->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        // Make API request
        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => $debitCard->cardNumber->getValue(),
                'amount' => 500,
                'currency' => 'PLN',
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('success', $response['status']);
        self::assertSame('Cash withdrawn successfully from ATM', $response['message']);
        self::assertStringContainsString('****', $response['data']['cardNumber']);
        self::assertEquals(500.0, $response['data']['amount']); // Use assertEquals for type coercion

        // Verify balance was decreased
        $accountAfter = $this->bankAccountRepository->findById($account->id);
        self::assertNotNull($accountAfter);
        self::assertSame(50000, $accountAfter->balance->getAmount());
    }

    public function testAtmWithdrawalWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/transactions/atm-withdrawal',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_API_KEY' => $this->apiKey],
            'invalid json',
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
    }

    public function testAtmWithdrawalWithMissingCardNumber(): void
    {
        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'amount' => 100,
                'currency' => 'PLN',
            ],
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertSame('Validation failed', $response['message']);
        self::assertArrayHasKey('errors', $response);
    }

    public function testAtmWithdrawalWithMissingAmount(): void
    {
        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => '1234567890123456',
            ],
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertSame('Validation failed', $response['message']);
    }

    public function testAtmWithdrawalWithNegativeAmount(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $debitCard = $this->issueDebitCard($account->id);

        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => $debitCard->cardNumber->getValue(),
                'amount' => -50,
                'currency' => 'PLN',
            ],
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertSame('Validation failed', $response['message']);
    }

    public function testAtmWithdrawalWithZeroAmount(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $debitCard = $this->issueDebitCard($account->id);

        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => $debitCard->cardNumber->getValue(),
                'amount' => 0,
                'currency' => 'PLN',
            ],
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertSame('Validation failed', $response['message']);
    }

    public function testAtmWithdrawalWithNonExistentCard(): void
    {
        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => '1234567890123456',
                'amount' => 100,
                'currency' => 'PLN',
            ],
        );

        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertSame('Debit card with number "1234567890123456" not found', $response['message']);
    }

    public function testAtmWithdrawalWithInsufficientFunds(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $debitCard = $this->issueDebitCard($account->id);

        // Add small balance
        $account->deposit(new Money(10000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        // Try to withdraw more than available
        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => $debitCard->cardNumber->getValue(),
                'amount' => 500,
                'currency' => 'PLN',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertStringContainsString('Insufficient funds', $response['message']);
    }

    public function testAtmWithdrawalWithBlockedCard(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $debitCard = $this->issueDebitCard($account->id);

        // Block card
        $debitCard->block();
        $this->debitCardRepository->save($debitCard);

        // Add balance
        $account->deposit(new Money(100000, Currency::PLN));
        $this->bankAccountRepository->save($account);

        // Try to withdraw with blocked card
        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => $debitCard->cardNumber->getValue(),
                'amount' => 100,
                'currency' => 'PLN',
            ],
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('error', $response['status']);
        self::assertSame('Debit card is blocked or inactive', $response['message']);
    }

    public function testAtmWithdrawalCreatesTransaction(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');

        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'EUR',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $account = $this->bankAccountRepository->findByCustomerId($customerId)[0];

        $debitCard = $this->issueDebitCard($account->id);

        // Add initial balance
        $account->deposit(new Money(50000, Currency::EUR));
        $this->bankAccountRepository->save($account);

        $this->makeApiRequest(
            'POST',
            '/api/transactions/atm-withdrawal',
            [
                'cardNumber' => $debitCard->cardNumber->getValue(),
                'amount' => 150,
                'currency' => 'EUR',
            ],
        );

        $this->assertResponseIsSuccessful();

        // Verify transaction was created
        $transactions = $this->transactionRepository->findByBankAccountId(
            BankAccountIdVO::fromString($account->id->getValue()),
        );

        self::assertCount(1, $transactions);
        self::assertSame(TransactionType::ATM_WITHDRAWAL, $transactions[0]->type);
        self::assertSame(15000, $transactions[0]->amount->getAmount());
    }

    private function issueDebitCard(BankAccountId $bankAccountId): DebitCard
    {
        $cardNumber = $this->debitCardRepository->generateCardNumber();
        $debitCard = DebitCard::issue(
            $this->debitCardRepository->nextIdentity(),
            $cardNumber,
            $bankAccountId,
        );

        $this->debitCardRepository->save($debitCard);

        return $debitCard;
    }
}
