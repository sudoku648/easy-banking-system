<?php

declare(strict_types=1);

namespace App\Transaction\Presentation\Form;

use App\Transaction\Presentation\Dto\TransferMoneyDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TransferMoneyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<int, array{id: string, iban: string, balance: int, currency: string}> $accounts */
        $accounts = $options['accounts'] ?? [];

        $builder
            ->add('fromBankAccountId', ChoiceType::class, [
                'label' => 'From Account',
                'choices' => $accounts,
                'choice_label' => function (mixed $account): string {
                    if (!\is_array($account)) {
                        return '';
                    }
                    /** @var array{iban: string, balance: float|int, currency: string} $account */
                    return $account['iban'] . ' (' . number_format((float) $account['balance'], 2) . ' ' . $account['currency'] . ')';
                },
                'choice_value' => function (mixed $account): string {
                    if (!\is_array($account)) {
                        return '';
                    }
                    /** @var array{id: string} $account */
                    return $account['id'];
                },
                'placeholder' => '-- Select source account --',
            ])
            ->add('toIban', TextType::class, [
                'label' => 'To IBAN',
                'attr' => ['placeholder' => 'PL12345678901234567890123456'],
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
            'data_class' => TransferMoneyDto::class,
            'accounts' => [],
        ]);
    }
}
