<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Dto;

use App\BankAccount\Api\Frontend\Dto\Part\AddressDto;
use Symfony\Component\Validator\Constraints as Assert;

final class OpenAccountNewCustomerDto
{
    /**
     * @param list<AddressDto> $correspondenceAddresses
     */
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 50)]
        public string $username,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        #[\SensitiveParameter]
        public string $password,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public string $firstName,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public string $lastName,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['PLN', 'EUR', 'USD', 'GBP'])]
        public string $currency,
        #[Assert\Type(type: AddressDto::class)]
        #[Assert\NotBlank]
        #[Assert\Valid]
        public AddressDto $permanentResidence,
        #[Assert\Valid]
        public array $correspondenceAddresses = [],
    ) {
    }
}
