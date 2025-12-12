<?php

declare(strict_types=1);

namespace App\Transaction\Api\External\Controller;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Infrastructure\Http\ApiSuccessResponse;
use App\Shared\Infrastructure\Http\Attribute\DynamicDto;
use App\Shared\Infrastructure\Http\InternalServerErrorResponse;
use App\Transaction\Api\External\Dto\WithdrawCashFromAtmDto;
use App\Transaction\Application\Command\WithdrawCashFromAtmCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/external/atm-withdrawal', name: 'api_atm_withdrawal', methods: ['POST'])]
final class AtmWithdrawalController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(
        #[DynamicDto]
        WithdrawCashFromAtmDto $dto,
    ): JsonResponse {
        try {
            // Convert amount to cents
            $amountInCents = (int) round($dto->amount * 100);

            $this->handle(
                new WithdrawCashFromAtmCommand(
                    $dto->cardNumber,
                    $amountInCents,
                    $dto->currency,
                ),
            );

            return new ApiSuccessResponse(
                message: 'Cash withdrawn successfully from ATM',
                data: [
                    'cardNumber' => '****-****-****-' . substr($dto->cardNumber, -4),
                    'amount' => $dto->amount,
                ],
                statusCode: Response::HTTP_OK,
            )->toJsonResponse();
        } catch (HandlerFailedException $e) {
            // Unwrap the original exception from Symfony Messenger
            $originalException = $e->getPrevious() ?? $e;

            if ($originalException instanceof DomainException) {
                return new JsonResponse(
                    [
                        'status' => 'error',
                        'message' => $originalException->getMessage(),
                    ],
                    $originalException->getStatusCode(),
                );
            }

            throw $e; // Re-throw if not a domain exception
        } catch (DomainException $e) {
            return new JsonResponse(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
                $e->getStatusCode(),
            );
        } catch (\Exception $e) {
            return new InternalServerErrorResponse()->toJsonResponse();
        }
    }
}
