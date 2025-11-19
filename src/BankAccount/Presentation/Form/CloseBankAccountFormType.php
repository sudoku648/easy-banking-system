<?php

declare(strict_types=1);

namespace App\BankAccount\Presentation\Form;

use App\BankAccount\Presentation\Dto\CloseBankAccountDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CloseBankAccountFormType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
        $accounts = $options['accounts'] ?? [];

        $builder
            ->add('bankAccountId', ChoiceType::class, [
                'label' => 'Bank Account',
                'choices' => $accounts,
                'choice_label' => function (mixed $account): string {
                    if (!\is_array($account)) {
                        return '';
                    }
                    /** @var array{iban: string, balance: int, currency: string} $account */
                    return $account['iban'] . ' (' . number_format($account['balance'] / 100, 2) . ' ' . $account['currency'] . ')';
                },
                'choice_value' => function (mixed $account): string {
                    if (!\is_array($account)) {
                        return '';
                    }
                    /** @var array{id: string} $account */
                    return $account['id'];
                },
                'placeholder' => '-- Select account to close --',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CloseBankAccountDto::class,
            'accounts' => [],
        ]);
    }
}
