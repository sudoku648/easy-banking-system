<?php

declare(strict_types=1);

namespace App\BankAccount\Domain\Entity;

use App\BankAccount\Domain\Exception\DebitCardStateException;
use App\BankAccount\Domain\ValueObject\BankAccountId;
use App\BankAccount\Domain\ValueObject\DebitCardId;
use App\BankAccount\Domain\ValueObject\DebitCardNumber;

final class DebitCard
{
    private function __construct(
        public readonly DebitCardId $id,
        public readonly DebitCardNumber $cardNumber,
        public readonly BankAccountId $bankAccountId,
        public private(set) bool $isActive = true,
        public readonly \DateTimeImmutable $issuedAt = new \DateTimeImmutable(),
        public private(set) ?\DateTimeImmutable $blockedAt = null,
    ) {
    }

    /**
     * @param array{
     *   id: string,
     *   card_number: string,
     *   bank_account_id: string,
     *   is_active: bool,
     *   issued_at: string,
     *   blocked_at: ?string,
     * } $data
     */
    public static function fromRaw(array $data): self
    {
        return new self(
            DebitCardId::fromString($data['id']),
            DebitCardNumber::fromString($data['card_number']),
            BankAccountId::fromString($data['bank_account_id']),
            (bool) $data['is_active'],
            new \DateTimeImmutable($data['issued_at']),
            null !== $data['blocked_at'] ? new \DateTimeImmutable($data['blocked_at']) : null,
        );
    }

    public static function issue(
        DebitCardId $id,
        DebitCardNumber $cardNumber,
        BankAccountId $bankAccountId,
    ): self {
        return new self(
            $id,
            $cardNumber,
            $bankAccountId,
        );
    }

    public function block(): void
    {
        if (!$this->isActive) {
            throw DebitCardStateException::alreadyBlocked();
        }

        $this->isActive = false;
        $this->blockedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        if ($this->isActive) {
            throw DebitCardStateException::alreadyActive();
        }

        $this->isActive = true;
        $this->blockedAt = null;
    }

    public function canBeUsedForWithdrawal(): bool
    {
        return $this->isActive;
    }
}
