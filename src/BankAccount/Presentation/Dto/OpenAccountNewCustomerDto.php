<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class OpenAccountNewCustomerDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public string $username = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public string $firstName = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public string $lastName = '';

    #[Assert\NotBlank]
    #[Assert\Choice(['PLN', 'EUR'])]
    public string $currency = '';
}
