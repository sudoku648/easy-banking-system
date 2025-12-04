<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Api;

use App\BankAccount\Application\Command\BlockDebitCardCommand;
use App\BankAccount\Application\Command\CloseBankAccountCommand;
use App\BankAccount\Application\Command\IssueDebitCardCommand;
use App\BankAccount\Application\Command\OpenBankAccountCommand;
use App\BankAccount\Application\Query\GetAllActiveBankAccountsQuery;
use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Application\Query\GetDebitCardsByBankAccountIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\BadRequestResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\Transaction\Application\Command\DepositMoneyCommand;
use App\Transaction\Application\Query\GetTransactionHistoryQuery;
use App\Transaction\Domain\Entity\Transaction;
use App\UserManagement\Application\Command\CreateCustomerCommand;
use App\UserManagement\Application\Query\GetAllCustomersQuery;
use App\UserManagement\Domain\Entity\Customer;
use App\UserManagement\Domain\Entity\User;
use App\UserManagement\Domain\Persistence\Repository\UserRepositoryInterface;
use App\UserManagement\Domain\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/employee')]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeApiController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly UserRepositoryInterface $userRepository,
        private readonly BankAccountRepositoryInterface $bankAccountRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/customers', name: 'api_employee_customers', methods: ['GET'])]
    public function getCustomers(): JsonResponse
    {
        try {
            /** @var array<array{id: string, username: string, firstName: string, lastName: string, fullName: string, isActive: bool}> $customers */
            $customers = $this->handle(new GetAllCustomersQuery());

            $customersData = array_map(
                fn (array $customer): array => [
                    'id' => $customer['id'],
                    'username' => $customer['username'],
                    'firstName' => $customer['firstName'],
                    'lastName' => $customer['lastName'],
                    'fullName' => $customer['fullName'],
                ],
                $customers,
            );

            return new ApiSuccessResponse(
                message: 'Customers retrieved successfully',
                data: ['customers' => $customersData],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/customers/{customerId}', name: 'api_employee_customer', methods: ['GET'])]
    public function getCustomer(string $customerId): JsonResponse
    {
        try {
            $user = $this->userRepository->findById(UserId::fromString($customerId));

            if (null === $user) {
                return new UnprocessableEntityResponse(
                    message: 'Customer not found',
                )->toJsonResponse();
            }

            if (!$user instanceof Customer) {
                return new UnprocessableEntityResponse(
                    message: 'User is not a customer',
                )->toJsonResponse();
            }

            $permanentResidence = $user->getPermanentResidenceAddress();
            $correspondenceAddresses = $user->getCorrespondenceAddresses();

            $customerData = [
                'id' => $user->id->getValue(),
                'username' => $user->username->getValue(),
                'firstName' => $user->firstName->getValue(),
                'lastName' => $user->lastName->getValue(),
                'permanentResidence' => null !== $permanentResidence ? [
                    'street' => $permanentResidence->address->street->getValue(),
                    'city' => $permanentResidence->address->city->getValue(),
                    'postalCode' => $permanentResidence->address->postalCode->getValue(),
                    'country' => $permanentResidence->address->country->getValue(),
                ] : null,
                'correspondenceAddresses' => array_map(
                    fn ($customerAddress): array => [
                        'street' => $customerAddress->address->street->getValue(),
                        'city' => $customerAddress->address->city->getValue(),
                        'postalCode' => $customerAddress->address->postalCode->getValue(),
                        'country' => $customerAddress->address->country->getValue(),
                    ],
                    $correspondenceAddresses,
                ),
            ];

            return new ApiSuccessResponse(
                message: 'Customer retrieved successfully',
                data: ['customer' => $customerData],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/customers/{customerId}/accounts', name: 'api_employee_customer_accounts', methods: ['GET'])]
    public function getCustomerAccounts(string $customerId): JsonResponse
    {
        try {
            /** @var array<BankAccount> $accounts */
            $accounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($customerId),
            );

            $accountsData = array_map(
                fn (BankAccount $account): array => [
                    'id' => $account->id->getValue(),
                    'iban' => $account->iban->getValue(),
                    'balance' => $account->balance->getAmount(),
                    'blockedAmount' => $account->blockedAmount->getAmount(),
                    'availableBalance' => $account->getAvailableBalance()->getAmount(),
                    'currency' => $account->balance->getCurrency()->value,
                    'isActive' => $account->isActive,
                ],
                $accounts,
            );

            return new ApiSuccessResponse(
                message: 'Accounts retrieved successfully',
                data: ['accounts' => $accountsData],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/accounts/{accountId}/transactions', name: 'api_employee_transactions', methods: ['GET'])]
    public function getTransactions(string $accountId): JsonResponse
    {
        try {
            /** @var array<Transaction> $transactions */
            $transactions = $this->handle(
                new GetTransactionHistoryQuery($accountId),
            );

            $transactionsData = array_map(
                fn (Transaction $transaction): array => [
                    'id' => $transaction->id->getValue(),
                    'type' => $transaction->type->value,
                    'amount' => $transaction->amount->getAmount(),
                    'currency' => $transaction->amount->getCurrency()->value,
                    'originalAmount' => $transaction->originalAmount->getAmount(),
                    'originalCurrency' => $transaction->originalAmount->getCurrency()->value,
                    'exchangeRate' => $transaction->exchangeRate->getRate(),
                    'status' => $transaction->status->value,
                    'occurredAt' => $transaction->occurredAt->format('Y-m-d H:i:s'),
                ],
                $transactions,
            );

            return new ApiSuccessResponse(
                message: 'Transactions retrieved successfully',
                data: ['transactions' => $transactionsData],
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/deposit', name: 'api_employee_deposit', methods: ['POST'])]
    public function deposit(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['accountId'], $data['amount'])) {
                return new BadRequestResponse(
                    message: 'Missing required fields',
                )->toJsonResponse();
            }

            $accountId = (string) $data['accountId'];
            $amountInCents = (int) $data['amount'];

            // Verify account exists
            $account = $this->bankAccountRepository->findById(
                BankAccountId::fromString($accountId),
            );

            if (null === $account) {
                return new UnprocessableEntityResponse(
                    message: 'Account not found',
                )->toJsonResponse();
            }

            $this->handle(
                new DepositMoneyCommand(
                    $accountId,
                    $amountInCents,
                    $account->balance->getCurrency()->value,
                ),
            );

            return new ApiSuccessResponse(
                message: 'Deposit completed successfully',
            )->toJsonResponse();
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/open-account-new-customer', name: 'api_employee_open_account_new', methods: ['POST'])]
    public function openAccountNewCustomer(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset(
                $data['username'],
                $data['password'],
                $data['firstName'],
                $data['lastName'],
                $data['permanentResidence'],
                $data['currency'],
            )) {
                return new BadRequestResponse(
                    message: 'Missing required fields',
                )->toJsonResponse();
            }

            $permanentResidence = $data['permanentResidence'];
            if (!isset(
                $permanentResidence['street'],
                $permanentResidence['city'],
                $permanentResidence['postalCode'],
                $permanentResidence['country'],
            )) {
                return new BadRequestResponse(
                    message: 'Missing permanent residence fields',
                )->toJsonResponse();
            }

            $correspondenceAddresses = $data['correspondenceAddresses'] ?? [];

            // Create customer
            /** @var string $customerId */
            $customerId = $this->handle(
                new CreateCustomerCommand(
                    username: (string) $data['username'],
                    password: (string) $data['password'],
                    firstName: (string) $data['firstName'],
                    lastName: (string) $data['lastName'],
                    permanentResidenceStreet: (string) $permanentResidence['street'],
                    permanentResidenceCity: (string) $permanentResidence['city'],
                    permanentResidencePostalCode: (string) $permanentResidence['postalCode'],
                    permanentResidenceCountry: (string) $permanentResidence['country'],
                    correspondenceAddresses: $correspondenceAddresses,
                ),
            );

            // Open account for the new customer
            /** @var string $accountId */
            $accountId = $this->handle(
                new OpenBankAccountCommand(
                    $customerId,
                    (string) $data['currency'],
                ),
            );

            return new ApiSuccessResponse(
                message: 'Customer created and account opened successfully',
                data: [
                    'customerId' => $customerId,
                    'accountId' => $accountId,
                ],
                statusCode: Response::HTTP_CREATED,
            )->toJsonResponse();
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/open-account-existing-customer', name: 'api_employee_open_account_existing', methods: ['POST'])]
    public function openAccountExistingCustomer(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['customerId'], $data['currency'])) {
                return new BadRequestResponse(
                    message: 'Missing required fields',
                )->toJsonResponse();
            }

            $customerId = (string) $data['customerId'];

            // Verify customer exists
            $customer = $this->userRepository->findById(UserId::fromString($customerId));
            if (null === $customer) {
                return new UnprocessableEntityResponse(
                    message: 'Customer not found',
                )->toJsonResponse();
            }

            /** @var string $accountId */
            $accountId = $this->handle(
                new OpenBankAccountCommand(
                    $customerId,
                    (string) $data['currency'],
                ),
            );

            return new ApiSuccessResponse(
                message: 'Account opened successfully',
                data: ['accountId' => $accountId],
                statusCode: Response::HTTP_CREATED,
            )->toJsonResponse();
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/close-account', name: 'api_employee_close_account', methods: ['POST'])]
    public function closeAccount(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['accountId'])) {
                return new BadRequestResponse(
                    message: 'Missing required field: accountId',
                )->toJsonResponse();
            }

            $accountId = (string) $data['accountId'];

            // Verify account exists
            $account = $this->bankAccountRepository->findById(
                BankAccountId::fromString($accountId),
            );

            if (null === $account) {
                return new UnprocessableEntityResponse(
                    message: 'Account not found',
                )->toJsonResponse();
            }

            $this->handle(
                new CloseBankAccountCommand($accountId),
            );

            return new ApiSuccessResponse(
                message: 'Account closed successfully',
            )->toJsonResponse();
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/issue-debit-card', name: 'api_employee_issue_card', methods: ['POST'])]
    public function issueDebitCard(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['accountId'])) {
                return new BadRequestResponse(
                    message: 'Missing required field: accountId',
                )->toJsonResponse();
            }

            $accountId = (string) $data['accountId'];

            // Verify account exists
            $account = $this->bankAccountRepository->findById(
                BankAccountId::fromString($accountId),
            );

            if (null === $account) {
                return new UnprocessableEntityResponse(
                    message: 'Account not found',
                )->toJsonResponse();
            }

            /** @var string $cardId */
            $cardId = $this->handle(
                new IssueDebitCardCommand($accountId),
            );

            return new ApiSuccessResponse(
                message: 'Debit card issued successfully',
                data: ['cardId' => $cardId],
                statusCode: Response::HTTP_CREATED,
            )->toJsonResponse();
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/block-debit-card', name: 'api_employee_block_card', methods: ['POST'])]
    public function blockDebitCard(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['cardId'])) {
                return new BadRequestResponse(
                    message: 'Missing required field: cardId',
                )->toJsonResponse();
            }

            $this->handle(
                new BlockDebitCardCommand((string) $data['cardId']),
            );

            return new ApiSuccessResponse(
                message: 'Debit card blocked successfully',
            )->toJsonResponse();
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/active-accounts', name: 'api_employee_active_accounts', methods: ['GET'])]
    public function getActiveAccounts(): JsonResponse
    {
        try {
            /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
            $accounts = $this->handle(new GetAllActiveBankAccountsQuery());

            return new ApiSuccessResponse(
                message: 'Active accounts retrieved successfully',
                data: $accounts,
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/debit-cards', name: 'api_employee_debit_cards', methods: ['GET'])]
    public function getAllDebitCards(): JsonResponse
    {
        try {
            /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
            $accounts = $this->handle(new GetAllActiveBankAccountsQuery());

            $allCards = [];
            foreach ($accounts as $account) {
                /** @var array<DebitCard> $cards */
                $cards = $this->handle(
                    new GetDebitCardsByBankAccountIdQuery($account['id']),
                );

                foreach ($cards as $card) {
                    if (!$card->isActive) {
                        continue;
                    }

                    $allCards[] = [
                        'id' => $card->id->getValue(),
                        'cardNumber' => $card->cardNumber->getValue(),
                        'iban' => $account['iban'],
                        'label' => sprintf('%s - %s', $card->cardNumber->getValue(), $account['iban']),
                        'isActive' => $card->isActive,
                    ];
                }
            }

            return new ApiSuccessResponse(
                message: 'Debit cards retrieved successfully',
                data: $allCards,
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
