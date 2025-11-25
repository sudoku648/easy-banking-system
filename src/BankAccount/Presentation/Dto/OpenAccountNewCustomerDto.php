<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class OpenAccountNewCustomerDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $username = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $firstName = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Choice(['PLN', 'EUR'])]
    public ?string $currency = null;
}
