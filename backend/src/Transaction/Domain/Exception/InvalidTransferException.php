<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;

final class InvalidTransferException extends ValidationException
{
    public static function useInternalTransferCommand(): self
    {
        return new self('Use TransferMoney command for internal transfers');
    }

    public static function insufficientBalance(): self
    {
        return new self('Insufficient available balance for transfer');
    }
}
