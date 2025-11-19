<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Form;

use App\Transaction\Presentation\Dto\DepositMoneyDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DepositMoneyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<int, array{id: string, iban: string, customerId: string, balance: int, currency: string}> $accounts */
        $accounts = $options['accounts'] ?? [];

        // Build choices array with labels as keys and IDs as values
        $choices = [];
        foreach ($accounts as $account) {
            $balance = (float) $account['balance'] / 100;
            $label = $account['iban'] . ' (' . number_format($balance, 2) . ' ' . $account['currency'] . ')';
            $choices[$label] = $account['id'];
        }

        $builder
            ->add('bankAccountId', ChoiceType::class, [
                'label' => 'Bank Account',
                'choices' => $choices,
                'placeholder' => '-- Select bank account --',
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Amount',
                'scale' => 2,
                'attr' => ['placeholder' => '0.00', 'step' => '0.01'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DepositMoneyDto::class,
            'accounts' => [],
        ]);
    }
}
