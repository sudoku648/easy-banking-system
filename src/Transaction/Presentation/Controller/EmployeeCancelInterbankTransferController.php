<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Controller;

use App\Transaction\Application\Command\CancelInterbankTransferCommand;
use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee/transaction/cancel', name: 'employee_transaction_cancel', methods: ['POST'])]
#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeCancelInterbankTransferController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): Response
    {
        $transactionId = $request->request->get('transactionId');
        $customerId = $request->request->get('customerId');
        $bankAccountId = $request->request->get('bankAccountId');

        if (!$transactionId) {
            $this->addFlash('danger', 'Transaction ID is required');
            return $this->redirectToRoute('employee_transaction_history_select');
        }

        try {
            /** @var SecurityUser $securityUser */
            $securityUser = $this->getUser();
            $user = $securityUser->getUser();

            $this->handle(
                new CancelInterbankTransferCommand(
                    transactionId: (string) $transactionId,
                    employeeId: $user->id->getValue(),
                ),
            );

            $this->addFlash('success', 'flash.transaction.cancelled');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error: ' . $e->getMessage());
        }

        // Redirect back to the history page
        $queryParams = [];
        if ($customerId) {
            $queryParams['customerId'] = $customerId;
        }
        if ($bankAccountId) {
            $queryParams['bankAccountId'] = $bankAccountId;
        }

        return $this->redirectToRoute('employee_transaction_history_view', $queryParams);
    }
}
