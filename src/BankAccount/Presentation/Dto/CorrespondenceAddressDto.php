<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CorrespondenceAddressDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    public ?string $street = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $city = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[0-9]{2}$/', message: 'Postal code first part must be 2 digits')]
    public ?string $postalCode1 = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[0-9]{3}$/', message: 'Postal code second part must be 3 digits')]
    public ?string $postalCode2 = null;

    #[Assert\NotBlank]
    public ?string $country = null;
}
