<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class SelectCustomerForHistoryDto
{
    public ?string $customerId = null;

    public ?string $bankAccountId = null;

    #[Assert\Callback]
    public function validatePasswordsMatch(ExecutionContextInterface $context): void
    {
        if (null === $this->customerId) {
            $context->buildViolation('transaction.validation.please_select_customer')
                ->atPath('customerId')
                ->setTranslationDomain('transaction')
                ->addViolation();
        }
    }
}
