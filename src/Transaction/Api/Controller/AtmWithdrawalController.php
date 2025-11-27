<?php

declare(strict_types=1);

namespace App\Transaction\Api\Controller;

use App\Transaction\Application\Command\WithdrawCashFromAtmCommand;
use App\Transaction\Presentation\Dto\WithdrawCashFromAtmDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transactions/atm-withdrawal', name: 'api_atm_withdrawal', methods: ['POST'])]
final class AtmWithdrawalController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            /** @var WithdrawCashFromAtmDto $dto */
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                WithdrawCashFromAtmDto::class,
                'json',
            );
        } catch (NotEncodableValueException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid JSON format',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Validate DTO
            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $errorMessages,
                ], Response::HTTP_BAD_REQUEST);
            }

            // Convert amount to cents
            $amountInCents = (int) round($dto->amount * 100);

            $this->handle(
                new WithdrawCashFromAtmCommand(
                    $dto->cardNumber,
                    $amountInCents,
                    $dto->currency,
                ),
            );

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Cash withdrawn successfully from ATM',
                'data' => [
                    'cardNumber' => '****-****-****-' . substr($dto->cardNumber, -4),
                    'amount' => $dto->amount,
                ],
            ], Response::HTTP_OK);
        } catch (HandlerFailedException $e) {
            // Unwrap the original exception from Symfony Messenger
            $originalException = $e->getPrevious() ?? $e;
            
            if ($originalException instanceof \DomainException) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $originalException->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }
            
            throw $e; // Re-throw if not a domain exception
        } catch (\DomainException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
