<?php

declare(strict_types=1);

namespace App\Tests\Api\BankAccount\Controller;

use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\Persistence\Repository\DebitCardRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\CustomerId;
use App\Tests\Api\ApiTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class CustomerDebitCardsControllerTest extends ApiTestCase
{
    private MessageBusInterface $messageBus;
    private BankAccountRepositoryInterface $bankAccountRepository;
    private DebitCardRepositoryInterface $debitCardRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBus = static::getContainer()->get(MessageBusInterface::class);
        $this->bankAccountRepository = static::getContainer()->get(BankAccountRepositoryInterface::class);
        $this->debitCardRepository = static::getContainer()->get(DebitCardRepositoryInterface::class);
    }

    public function testGetCustomerDebitCardsWithoutAuthentication(): void
    {
        $this->client->jsonRequest('GET', '/api/frontend/customer/debit-cards');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCustomerDebitCardsSuccessfully(): void
    {
        // Create customer and login
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->client->loginUser(new \App\UserManagement\Infrastructure\Security\SecurityUser($customer));

        // Create account
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue debit card
        $debitCard = $this->issueDebitCard($account->id);

        // Make request
        $this->client->jsonRequest('GET', '/api/frontend/customer/debit-cards');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('success', $response['status']);
        self::assertSame('Debit cards retrieved successfully', $response['message']);
        self::assertArrayHasKey('debitCards', $response['data']);
        self::assertCount(1, $response['data']['debitCards']);

        $card = $response['data']['debitCards'][0];
        self::assertSame($debitCard->id->getValue(), $card['id']);
        self::assertSame($debitCard->cardNumber->getValue(), $card['cardNumber']);
        self::assertSame($account->id->getValue(), $card['bankAccountId']);
        self::assertSame($account->iban->getValue(), $card['iban']);
        self::assertTrue($card['isActive']);
        self::assertArrayHasKey('issuedAt', $card);
    }

    public function testGetCustomerDebitCardsReturnsOnlyActiveCards(): void
    {
        // Create customer and login
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->client->loginUser(new \App\UserManagement\Infrastructure\Security\SecurityUser($customer));

        // Create account
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        $customerId = CustomerId::fromString($customer->id->getValue());
        $accounts = $this->bankAccountRepository->findByCustomerId($customerId);
        $account = $accounts[0];

        // Issue two debit cards
        $activeCard = $this->issueDebitCard($account->id);
        $blockedCard = $this->issueDebitCard($account->id);

        // Block one card
        $blockedCard->block();
        $this->debitCardRepository->save($blockedCard);

        // Make request
        $this->client->jsonRequest('GET', '/api/frontend/customer/debit-cards');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertCount(1, $response['data']['debitCards']);
        self::assertSame($activeCard->id->getValue(), $response['data']['debitCards'][0]['id']);
    }

    public function testGetCustomerDebitCardsReturnsEmptyArrayWhenNoCards(): void
    {
        // Create customer and login
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->client->loginUser(new \App\UserManagement\Infrastructure\Security\SecurityUser($customer));

        // Create account without debit card
        $this->messageBus->dispatch(
            new OpenBankAccountCommand(
                customerId: $customer->id->getValue(),
                currency: 'PLN',
            ),
        );

        // Make request
        $this->client->jsonRequest('GET', '/api/frontend/customer/debit-cards');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('success', $response['status']);
        self::assertArrayHasKey('debitCards', $response['data']);
        self::assertCount(0, $response['data']['debitCards']);
    }

    public function testGetCustomerDebitCardsFromMultipleAccounts(): void
    {
        // Create customer and login
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->client->loginUser(new \App\UserManagement\Infrastructure\Security\SecurityUser($customer));

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

        // Issue cards for both accounts
        $card1 = $this->issueDebitCard($accounts[0]->id);
        $card2 = $this->issueDebitCard($accounts[1]->id);

        // Make request
        $this->client->jsonRequest('GET', '/api/frontend/customer/debit-cards');

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertCount(2, $response['data']['debitCards']);

        $cardIds = array_column($response['data']['debitCards'], 'id');
        self::assertContains($card1->id->getValue(), $cardIds);
        self::assertContains($card2->id->getValue(), $cardIds);
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
