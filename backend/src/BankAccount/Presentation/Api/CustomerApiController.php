<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Api;

use App\BankAccount\Application\Command\BlockDebitCardCommand;
use App\BankAccount\Application\Query\GetBankAccountsByCustomerIdQuery;
use App\BankAccount\Application\Query\GetDebitCardsByBankAccountIdQuery;
use App\BankAccount\Domain\Entity\BankAccount;
use App\BankAccount\Domain\Entity\DebitCard;
use App\BankAccount\Domain\Exception\BankAccountNotFoundException;
use App\BankAccount\Domain\Persistence\Repository\BankAccountRepositoryInterface;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\Shared\Domain\Provider\IbanProviderInterface;
use App\Shared\Domain\ValueObject\Iban;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\BadRequestResponse;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Shared\Infrastructure\Http\UnprocessableEntityResponse;
use App\Transaction\Application\Command\InterbankTransferCommand;
use App\Transaction\Application\Command\TransferMoneyCommand;
use App\Transaction\Application\Query\GetTransactionHistoryQuery;
use App\Transaction\Domain\Entity\Transaction;
use App\UserManagement\Application\Command\ChangePasswordCommand;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customer')]
#[IsGranted('ROLE_CUSTOMER')]
final class CustomerApiController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly BankAccountRepositoryInterface $bankAccountRepository,
        private readonly IbanProviderInterface $ibanProvider,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/accounts', name: 'api_customer_accounts', methods: ['GET'])]
    public function getAccounts(): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->getUser();

        /** @var array<BankAccount> $accounts */
        $accounts = $this->handle(
            new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
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
    }

    #[Route('/accounts/{accountId}/transactions', name: 'api_customer_transactions', methods: ['GET'])]
    public function getTransactions(string $accountId): JsonResponse
    {
        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            // Verify account belongs to user
            /** @var array<BankAccount> $accounts */
            $accounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
            );

            $accountIds = array_map(fn (BankAccount $account): string => $account->id->getValue(), $accounts);
            if (!in_array($accountId, $accountIds, true)) {
                return new UnprocessableEntityResponse(
                    message: 'Account not found or does not belong to you',
                )->toJsonResponse();
            }

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

    #[Route('/transfer', name: 'api_customer_transfer', methods: ['POST'])]
    public function transfer(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['sourceAccountId'], $data['recipientIban'], $data['amount'], $data['title'])) {
                return new BadRequestResponse(
                    message: 'Missing required fields',
                )->toJsonResponse();
            }

            $sourceAccountId = (string) $data['sourceAccountId'];
            $recipientIban = (string) $data['recipientIban'];
            $amountInCents = (int) $data['amount'];
            $title = (string) $data['title'];

            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            // Verify account belongs to user
            /** @var array<BankAccount> $accounts */
            $accounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
            );

            $accountIds = array_map(fn (BankAccount $account): string => $account->id->getValue(), $accounts);
            if (!in_array($sourceAccountId, $accountIds, true)) {
                return new UnprocessableEntityResponse(
                    message: 'Source account not found or does not belong to you',
                )->toJsonResponse();
            }

            $toIban = Iban::fromString($recipientIban);
            $fromAccount = $this->bankAccountRepository->findById(
                BankAccountId::fromString($sourceAccountId),
            );

            if (null === $fromAccount) {
                throw BankAccountNotFoundException::withId($sourceAccountId);
            }

            // Check if this is an internal or external transfer
            if ($this->ibanProvider->isInternalIban($toIban)) {
                // Internal transfer
                $toAccount = $this->bankAccountRepository->findByIban($toIban);

                if (null === $toAccount) {
                    throw BankAccountNotFoundException::withIban($toIban->getValue());
                }

                $this->handle(
                    new TransferMoneyCommand(
                        $sourceAccountId,
                        $toAccount->id->getValue(),
                        $amountInCents,
                        $fromAccount->balance->getCurrency()->value,
                    ),
                );

                return new ApiSuccessResponse(
                    message: 'Transfer completed successfully',
                )->toJsonResponse();
            } else {
                // External/interbank transfer
                $this->handle(
                    new InterbankTransferCommand(
                        $sourceAccountId,
                        $toIban->getValue(),
                        $amountInCents,
                        $fromAccount->balance->getCurrency()->value,
                    ),
                );

                return new ApiSuccessResponse(
                    message: 'Interbank transfer initiated successfully',
                )->toJsonResponse();
            }
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/change-password', name: 'api_customer_change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['currentPassword'], $data['newPassword'])) {
                return new BadRequestResponse(
                    message: 'Missing required fields',
                )->toJsonResponse();
            }

            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            $this->handle(
                new ChangePasswordCommand(
                    userId: $user->id,
                    currentPassword: (string) $data['currentPassword'],
                    newPassword: (string) $data['newPassword'],
                ),
            );

            return new ApiSuccessResponse(
                message: 'Password changed successfully',
            )->toJsonResponse();
        } catch (\DomainException $e) {
            return new UnprocessableEntityResponse(
                message: $e->getMessage(),
            )->toJsonResponse();
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }

    #[Route('/block-debit-card', name: 'api_customer_block_card', methods: ['POST'])]
    public function blockDebitCard(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['accountId'])) {
                return new BadRequestResponse(
                    message: 'Missing required field: accountId',
                )->toJsonResponse();
            }

            $accountId = (string) $data['accountId'];

            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            // Verify account belongs to user
            /** @var array<BankAccount> $bankAccounts */
            $bankAccounts = $this->handle(
                new GetBankAccountsByCustomerIdQuery($user->id->getValue()),
            );

            $accountIds = array_map(fn (BankAccount $account): string => $account->id->getValue(), $bankAccounts);
            if (!in_array($accountId, $accountIds, true)) {
                return new UnprocessableEntityResponse(
                    message: 'Account not found or does not belong to you',
                )->toJsonResponse();
            }

            // Get active debit cards for this account
            /** @var array<DebitCard> $cards */
            $cards = $this->handle(
                new GetDebitCardsByBankAccountIdQuery($accountId),
            );

            $activeCard = null;
            foreach ($cards as $card) {
                if ($card->isActive) {
                    $activeCard = $card;
                    break;
                }
            }

            if (null === $activeCard) {
                return new UnprocessableEntityResponse(
                    message: 'No active debit card found for this account',
                )->toJsonResponse();
            }

            $this->handle(
                new BlockDebitCardCommand($activeCard->id->getValue()),
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
}
