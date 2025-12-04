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
    #[Assert\Choice(['PLN', 'EUR', 'USD', 'GBP'])]
    public ?string $currency = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    public ?string $permanentResidenceStreet = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    public ?string $permanentResidenceCity = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[0-9]{2}$/', message: 'Postal code first part must be 2 digits')]
    public ?string $permanentResidencePostalCode1 = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[0-9]{3}$/', message: 'Postal code second part must be 3 digits')]
    public ?string $permanentResidencePostalCode2 = null;

    #[Assert\NotBlank]
    public ?string $permanentResidenceCountry = null;

    public bool $sameAsPermament = false;

    /**
     * @var CorrespondenceAddressDto[]
     */
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'At least one correspondence address is required')]
    public array $correspondenceAddresses = [];

    public function __construct()
    {
        // Initialize with one empty correspondence address
        $this->correspondenceAddresses = [new CorrespondenceAddressDto()];
    }
}
