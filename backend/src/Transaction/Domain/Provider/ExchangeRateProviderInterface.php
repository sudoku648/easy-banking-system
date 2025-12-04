<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Provider;

use App\Shared\Domain\ValueObject\Currency;
use App\Transaction\Domain\ValueObject\ExchangeRate;

interface ExchangeRateProviderInterface
{
    public function getRate(Currency $from, Currency $to): ExchangeRate;
}
