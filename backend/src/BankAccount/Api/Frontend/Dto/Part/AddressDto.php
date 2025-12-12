<?php

declare(strict_types=1);

namespace App\BankAccount\Api\Frontend\Dto\Part;

use Symfony\Component\Validator\Constraints as Assert;

final class AddressDto
{
    public function __construct(
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        public string $street,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        public string $city,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        public string $postalCode,
        #[Assert\Type(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['PL'])]
        public string $country,
    ) {
    }
}
