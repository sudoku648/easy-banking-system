<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\Validator;

use App\UserManagement\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class CurrentPasswordValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CurrentPassword) {
            throw new UnexpectedTypeException($constraint, CurrentPassword::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof SecurityUser) {
            return;
        }

        $domainUser = $user->getUser();
        if (!$domainUser->password->verify((string) $value)) {
            $this->context->buildViolation($constraint->message)
                ->setTranslationDomain('app')
                ->addViolation();
        }
    }
}
